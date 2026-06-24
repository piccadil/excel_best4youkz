<?php
declare(strict_types=1);

/**
 * Public config — safe to commit and deploy via git sync.
 * SMTP password: set SMTP_PASS env var, or create api/secrets.php on the server (gitignored).
 */
$config = [
    'smtp_host'      => getenv('SMTP_HOST') ?: 'best4you.kz',
    'smtp_port'      => (int) (getenv('SMTP_PORT') ?: 465),
    'smtp_secure'    => getenv('SMTP_SECURE') ?: 'ssl',
    'smtp_user'      => getenv('SMTP_USER') ?: 'form@best4you.kz',
    'smtp_pass'      => getenv('SMTP_PASS') ?: '',
    'mail_from'      => getenv('MAIL_FROM') ?: 'form@best4you.kz',
    'mail_from_name' => getenv('MAIL_FROM_NAME') ?: 'Best Training Excel',
    'mail_to'        => getenv('MAIL_TO') ?: 'info@best4you.kz',
    'rate_limit'     => (int) (getenv('RATE_LIMIT') ?: 5),
    'rate_window'    => (int) (getenv('RATE_WINDOW') ?: 3600),
];

if ($config['smtp_pass'] === '') {
    $secretsPath = __DIR__ . '/secrets.php';
    if (is_file($secretsPath)) {
        $secrets = require $secretsPath;
        if (is_array($secrets) && !empty($secrets['smtp_pass'])) {
            $config['smtp_pass'] = (string) $secrets['smtp_pass'];
        }
    }
}

return $config;
