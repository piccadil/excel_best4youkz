<?php
declare(strict_types=1);

/**
 * Minimal SMTP mailer (no external dependencies).
 */
function send_mail_smtp(array $config, array $message): void
{
    $host = (string) ($config['smtp_host'] ?? '');
    $port = (int) ($config['smtp_port'] ?? 587);
    $secure = (string) ($config['smtp_secure'] ?? 'tls');
    $user = (string) ($config['smtp_user'] ?? '');
    $pass = (string) ($config['smtp_pass'] ?? '');
    $from = (string) ($config['mail_from'] ?? 'noreply@best4you.kz');
    $fromName = (string) ($config['mail_from_name'] ?? 'BestTrain');
    $to = (string) ($message['to'] ?? '');
    $subject = (string) ($message['subject'] ?? '');
    $body = (string) ($message['body'] ?? '');
    $replyTo = $message['reply_to'] ?? null;

    if ($host === '' || $to === '') {
        throw new RuntimeException('SMTP is not configured');
    }

    $remote = ($secure === 'ssl' ? 'ssl://' : '') . $host . ':' . $port;
    $socket = @stream_socket_client($remote, $errno, $errstr, 20, STREAM_CLIENT_CONNECT);
    if (!$socket) {
        throw new RuntimeException('SMTP connection failed: ' . $errstr);
    }

    stream_set_timeout($socket, 20);
    smtp_expect($socket, [220]);
    smtp_cmd($socket, 'EHLO best4you.kz', [250]);

    if ($secure === 'tls') {
        smtp_cmd($socket, 'STARTTLS', [220]);
        if (!stream_socket_enable_crypto($socket, true, STREAM_CRYPTO_METHOD_TLS_CLIENT)) {
            throw new RuntimeException('STARTTLS failed');
        }
        smtp_cmd($socket, 'EHLO best4you.kz', [250]);
    }

    if ($user !== '') {
        smtp_cmd($socket, 'AUTH LOGIN', [334]);
        smtp_cmd($socket, base64_encode($user), [334]);
        smtp_cmd($socket, base64_encode($pass), [235]);
    }

    smtp_cmd($socket, 'MAIL FROM:<' . $from . '>', [250]);
    smtp_cmd($socket, 'RCPT TO:<' . $to . '>', [250, 251]);
    smtp_cmd($socket, 'DATA', [354]);

    $encodedSubject = '=?UTF-8?B?' . base64_encode($subject) . '?=';
    $headers = [
        'MIME-Version: 1.0',
        'Content-Type: text/html; charset=UTF-8',
        'From: ' . encode_address($fromName, $from),
        'To: <' . $to . '>',
        'Subject: ' . $encodedSubject,
        'Date: ' . gmdate('D, d M Y H:i:s') . ' +0000',
    ];
    if (is_string($replyTo) && $replyTo !== '') {
        $headers[] = 'Reply-To: <' . $replyTo . '>';
    }

    $payload = implode("\r\n", $headers) . "\r\n\r\n" . $body . "\r\n.";
    fwrite($socket, $payload . "\r\n");
    smtp_expect($socket, [250]);
    smtp_cmd($socket, 'QUIT', [221]);
    fclose($socket);
}

function encode_address(string $name, string $email): string
{
    $encoded = '=?UTF-8?B?' . base64_encode($name) . '?=';
    return $encoded . ' <' . $email . '>';
}

function smtp_cmd($socket, string $cmd, array $okCodes): string
{
    fwrite($socket, $cmd . "\r\n");
    return smtp_expect($socket, $okCodes);
}

function smtp_expect($socket, array $okCodes): string
{
    $response = '';
    while (($line = fgets($socket, 515)) !== false) {
        $response .= $line;
        if (isset($line[3]) && $line[3] === ' ') {
            break;
        }
    }
    $code = (int) substr($response, 0, 3);
    if (!in_array($code, $okCodes, true)) {
        throw new RuntimeException('SMTP error: ' . trim($response));
    }
    return $response;
}
