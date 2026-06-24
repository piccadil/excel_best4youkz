<?php
declare(strict_types=1);

/**
 * Form submission handler for excel.best4you.kz.
 * Config: api/config.php (git). Password: api/secrets.php on server or SMTP_PASS env.
 */

header('Content-Type: application/json; charset=utf-8');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['ok' => false, 'error' => 'Method not allowed']);
    exit;
}

$configPath = __DIR__ . '/config.php';
if (!is_file($configPath)) {
    http_response_code(500);
    echo json_encode(['ok' => false, 'error' => 'Server configuration missing']);
    exit;
}

$config = require $configPath;

require_once __DIR__ . '/lib/mailer.php';

function client_ip(): string
{
    $keys = ['HTTP_CF_CONNECTING_IP', 'HTTP_X_FORWARDED_FOR', 'REMOTE_ADDR'];
    foreach ($keys as $key) {
        if (empty($_SERVER[$key])) {
            continue;
        }
        $value = trim(explode(',', (string) $_SERVER[$key])[0]);
        if (filter_var($value, FILTER_VALIDATE_IP)) {
            return $value;
        }
    }
    return '0.0.0.0';
}

function rate_limit_exceeded(array $config, string $ip): bool
{
    $limit = (int) ($config['rate_limit'] ?? 5);
    $window = (int) ($config['rate_window'] ?? 3600);
    $dir = __DIR__ . '/logs';
    if (!is_dir($dir)) {
        mkdir($dir, 0750, true);
    }

    $file = $dir . '/rate-' . hash('sha256', $ip) . '.json';
    $now = time();
    $data = ['count' => 0, 'start' => $now];

    if (is_file($file)) {
        $decoded = json_decode((string) file_get_contents($file), true);
        if (is_array($decoded)) {
            $data = $decoded;
        }
        if (($now - (int) $data['start']) > $window) {
            $data = ['count' => 0, 'start' => $now];
        }
    }

    $data['count'] = (int) $data['count'] + 1;
    file_put_contents($file, json_encode($data), LOCK_EX);

    return $data['count'] > $limit;
}

function clean_string(string $value, int $max = 500): string
{
    $value = trim(strip_tags($value));
    if (function_exists('mb_substr')) {
        return mb_substr($value, 0, $max);
    }
    return substr($value, 0, $max);
}

function log_submission(array $config, string $status, string $ip): void
{
    $dir = __DIR__ . '/logs';
    if (!is_dir($dir)) {
        mkdir($dir, 0750, true);
    }
    $line = gmdate('c') . "\t" . $status . "\t" . hash('sha256', $ip) . PHP_EOL;
    file_put_contents($dir . '/submissions.log', $line, FILE_APPEND | LOCK_EX);
}

$ip = client_ip();
if (rate_limit_exceeded($config, $ip)) {
    http_response_code(429);
    log_submission($config, 'rate_limited', $ip);
    echo json_encode(['ok' => false, 'error' => 'Слишком много запросов. Попробуйте позже.']);
    exit;
}

if (!empty($_POST['website'])) {
    log_submission($config, 'honeypot', $ip);
    echo json_encode(['ok' => true]);
    exit;
}

$name = clean_string((string) ($_POST['name'] ?? ''), 120);
$email = clean_string((string) ($_POST['email'] ?? ''), 120);
$phone = clean_string((string) ($_POST['phone'] ?? ''), 60);
$message = clean_string((string) ($_POST['message'] ?? ''), 500);
$from = clean_string((string) ($_POST['from'] ?? ''), 300);

if ($name === '' || $phone === '') {
    http_response_code(422);
    echo json_encode(['ok' => false, 'error' => 'Укажите имя и телефон.']);
    exit;
}

if ($email !== '' && !filter_var($email, FILTER_VALIDATE_EMAIL)) {
    http_response_code(422);
    echo json_encode(['ok' => false, 'error' => 'Некорректный email.']);
    exit;
}

$phoneDigits = preg_replace('/\D+/', '', $phone) ?? '';
if (strlen($phoneDigits) < 10) {
    http_response_code(422);
    echo json_encode(['ok' => false, 'error' => 'Некорректный номер телефона.']);
    exit;
}

$subject = 'Новая заявка | ' . $phone;
$body = '<!DOCTYPE html><html lang="ru"><head><meta charset="UTF-8"></head><body>'
    . '<h3>' . htmlspecialchars(gmdate('d.m.Y H:i') . ' UTC', ENT_QUOTES, 'UTF-8') . '</h3>'
    . '<p><strong>Откуда:</strong> ' . htmlspecialchars($from, ENT_QUOTES, 'UTF-8') . '</p>'
    . '<p><strong>Имя:</strong> ' . htmlspecialchars($name, ENT_QUOTES, 'UTF-8') . '</p>'
    . '<p><strong>Телефон:</strong> ' . htmlspecialchars($phone, ENT_QUOTES, 'UTF-8') . '</p>';

if ($email !== '') {
    $body .= '<p><strong>Email:</strong> ' . htmlspecialchars($email, ENT_QUOTES, 'UTF-8') . '</p>';
}
if ($message !== '') {
    $body .= '<p><strong>Сообщение:</strong> ' . nl2br(htmlspecialchars($message, ENT_QUOTES, 'UTF-8')) . '</p>';
}
$body .= '</body></html>';

try {
    send_mail_smtp($config, [
        'to' => $config['mail_to'],
        'subject' => $subject,
        'body' => $body,
        'reply_to' => $email !== '' ? $email : null,
    ]);
    log_submission($config, 'sent', $ip);
    echo json_encode(['ok' => true]);
} catch (Throwable $e) {
    http_response_code(500);
    log_submission($config, 'error', $ip);
    error_log('best4you form error: ' . $e->getMessage());
    echo json_encode(['ok' => false, 'error' => 'Не удалось отправить заявку. Попробуйте позже.']);
}
