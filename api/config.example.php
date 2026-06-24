<?php
declare(strict_types=1);

return [
    'smtp_host'     => getenv('SMTP_HOST') ?: 'smtp.example.com',
    'smtp_port'     => (int) (getenv('SMTP_PORT') ?: 587),
    'smtp_secure'   => getenv('SMTP_SECURE') ?: 'tls',
    'smtp_user'     => getenv('SMTP_USER') ?: '',
    'smtp_pass'     => getenv('SMTP_PASS') ?: '',
    'mail_from'     => getenv('MAIL_FROM') ?: 'noreply@best4you.kz',
    'mail_from_name'=> getenv('MAIL_FROM_NAME') ?: 'BestTrain',
    'mail_to'       => getenv('MAIL_TO') ?: 'kolbasov.alex@gmail.com',
    'rate_limit'    => (int) (getenv('RATE_LIMIT') ?: 5),
    'rate_window'   => (int) (getenv('RATE_WINDOW') ?: 3600),
];
