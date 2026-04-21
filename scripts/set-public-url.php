<?php

if ($argc < 2 || trim($argv[1]) === '') {
    fwrite(STDERR, "Usage: php scripts/set-public-url.php https://your-public-url\n");
    exit(1);
}

$url = rtrim(trim($argv[1]), '/');

if (! filter_var($url, FILTER_VALIDATE_URL) || ! str_starts_with($url, 'https://')) {
    fwrite(STDERR, "Public URL must be a valid https:// URL.\n");
    exit(1);
}

$envPath = __DIR__.'/../.env';

if (! file_exists($envPath)) {
    $examplePath = __DIR__.'/../.env.example';
    if (! copy($examplePath, $envPath)) {
        fwrite(STDERR, "Unable to create .env from .env.example.\n");
        exit(1);
    }
}

$updates = [
    'APP_URL' => $url,
    'GOOGLE_REDIRECT_URI' => $url.'/auth/google/callback',
    'MIDTRANS_CALLBACK_URL' => $url.'/payments/midtrans/callback',
];

$lines = file($envPath, FILE_IGNORE_NEW_LINES);
$seen = [];

foreach ($lines as &$line) {
    if (! preg_match('/^([A-Z0-9_]+)=/', $line, $matches)) {
        continue;
    }

    $key = $matches[1];

    if (! array_key_exists($key, $updates)) {
        continue;
    }

    $line = $key.'="'.$updates[$key].'"';
    $seen[$key] = true;
}
unset($line);

foreach ($updates as $key => $value) {
    if (! isset($seen[$key])) {
        $lines[] = $key.'="'.$value.'"';
    }
}

file_put_contents($envPath, implode(PHP_EOL, $lines).PHP_EOL);

echo "Updated .env public URL values to {$url}\n";
