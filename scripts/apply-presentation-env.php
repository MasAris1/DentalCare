<?php

$envPath = __DIR__.'/../.env';
$examplePath = __DIR__.'/../.env.example';

if (! file_exists($envPath)) {
    if (! copy($examplePath, $envPath)) {
        fwrite(STDERR, "Unable to create .env from .env.example.\n");
        exit(1);
    }
}

$lines = file($envPath, FILE_IGNORE_NEW_LINES);
$values = [];

foreach ($lines as $line) {
    if (preg_match('/^([A-Z0-9_]+)=(.*)$/', $line, $matches)) {
        $values[$matches[1]] = trim($matches[2], '"');
    }
}

$mailUsername = $values['MAIL_USERNAME'] ?? '';
$mailUsername = in_array($mailUsername, ['', 'null'], true) ? 'yourgmail@gmail.com' : $mailUsername;

$mailPassword = $values['MAIL_PASSWORD'] ?? '';
$mailPassword = in_array($mailPassword, ['', 'null'], true) ? 'your-16-character-gmail-app-password' : $mailPassword;

$updates = [
    'APP_ENV' => 'production',
    'APP_DEBUG' => 'false',
    'APP_BIND' => '127.0.0.1',
    'APP_PORT' => '8080',
    'VITE_PORT' => '5173',
    'LOG_LEVEL' => 'error',
    'DB_ROOT_PASSWORD' => $values['DB_ROOT_PASSWORD'] ?? 'root',
    'DB_FORWARD_PORT' => $values['DB_FORWARD_PORT'] ?? '3307',
    'MAIL_MAILER' => 'smtp',
    'MAIL_SCHEME' => 'tls',
    'MAIL_HOST' => 'smtp.gmail.com',
    'MAIL_PORT' => '587',
    'MAIL_USERNAME' => $mailUsername,
    'MAIL_PASSWORD' => $mailPassword,
    'MAIL_FROM_ADDRESS' => $mailUsername,
    'CLINIC_EMAIL' => $mailUsername,
    'MIDTRANS_IS_PRODUCTION' => 'false',
];

$seen = [];

foreach ($lines as &$line) {
    if (! preg_match('/^([A-Z0-9_]+)=/', $line, $matches)) {
        continue;
    }

    $key = $matches[1];

    if (! array_key_exists($key, $updates)) {
        continue;
    }

    $line = $key.'='.formatEnvValue($updates[$key]);
    $seen[$key] = true;
}
unset($line);

foreach ($updates as $key => $value) {
    if (! isset($seen[$key])) {
        $lines[] = $key.'='.formatEnvValue($value);
    }
}

file_put_contents($envPath, implode(PHP_EOL, $lines).PHP_EOL);

echo "Applied presentation defaults to .env.\n";
echo "Next: fill MAIL_USERNAME and MAIL_PASSWORD, then run make public-url URL=https://your-tunnel-url.\n";

function formatEnvValue(string $value): string
{
    if (in_array($value, ['true', 'false', 'null'], true) || preg_match('/^[0-9]+$/', $value)) {
        return $value;
    }

    if (preg_match('/^[A-Za-z0-9_@.\/:${}-]+$/', $value)) {
        return $value;
    }

    return '"'.str_replace('"', '\\"', $value).'"';
}
