<?php

/**
 * QC Lab DB Switcher
 *
 * Usage:
 *   php switch_db.php status
 *   php switch_db.php home
 *   php switch_db.php work
 */

$mode = $argv[1] ?? 'status';
$envPath = __DIR__ . '/.env';

if (!file_exists($envPath)) {
    fwrite(STDERR, "Error: .env file not found.\n");
    exit(1);
}

$envContent = file_get_contents($envPath);

function envValue(string $content, string $key): ?string
{
    if (!preg_match('/^' . preg_quote($key, '/') . '=(.*)$/m', $content, $matches)) {
        return null;
    }

    return trim($matches[1]);
}

function setEnvValue(string $content, string $key, string $value): string
{
    $line = $key . '=' . $value;

    if (preg_match('/^' . preg_quote($key, '/') . '=.*$/m', $content)) {
        return preg_replace('/^' . preg_quote($key, '/') . '=.*$/m', $line, $content);
    }

    return rtrim($content) . PHP_EOL . $line . PHP_EOL;
}

function getProfileConfig(string $content, string $prefix): array
{
    $requiredKeys = ['CONNECTION', 'HOST', 'PORT', 'DATABASE', 'USERNAME', 'PASSWORD'];
    $config = [];

    foreach ($requiredKeys as $key) {
        $fullKey = $prefix . '_' . $key;
        $value = envValue($content, $fullKey);

        if ($value === null) {
            fwrite(STDERR, "Error: missing {$fullKey} in .env\n");
            exit(1);
        }

        $config[strtolower($key)] = $value;
    }

    return $config;
}

$profiles = [
    'work' => getProfileConfig($envContent, 'WORK_DB'),
    'home' => getProfileConfig($envContent, 'HOME_DB'),
];

if (!array_key_exists($mode, $profiles) && $mode !== 'status') {
    fwrite(STDERR, "Unknown mode: {$mode}\n");
    fwrite(STDERR, "Usage: php switch_db.php [status|home|work]\n");
    exit(1);
}

if ($mode === 'status') {
    $currentProfile = envValue($envContent, 'DB_PROFILE') ?? 'unknown';
    $currentConnection = envValue($envContent, 'DB_CONNECTION') ?? 'unknown';
    $currentHost = envValue($envContent, 'DB_HOST') ?? 'unknown';
    $currentPort = envValue($envContent, 'DB_PORT') ?? 'unknown';
    $currentDatabase = envValue($envContent, 'DB_DATABASE') ?? 'unknown';
    $currentUser = envValue($envContent, 'DB_USERNAME') ?? 'unknown';

    echo "====================================\n";
    echo "  QC Database Switcher\n";
    echo "====================================\n";
    echo "Current Profile : {$currentProfile}\n";
    echo "Driver          : {$currentConnection}\n";
    echo "Host            : {$currentHost}\n";
    echo "Port            : {$currentPort}\n";
    echo "Database        : {$currentDatabase}\n";
    echo "Username        : {$currentUser}\n\n";
    echo "To switch, run:\n";
    echo "  php switch_db.php home\n";
    echo "  php switch_db.php work\n";
    exit(0);
}

$config = $profiles[$mode];

$envContent = setEnvValue($envContent, 'DB_PROFILE', $mode);
$envContent = setEnvValue($envContent, 'DB_CONNECTION', $config['connection']);
$envContent = setEnvValue($envContent, 'DB_HOST', $config['host']);
$envContent = setEnvValue($envContent, 'DB_PORT', $config['port']);
$envContent = setEnvValue($envContent, 'DB_DATABASE', $config['database']);
$envContent = setEnvValue($envContent, 'DB_USERNAME', $config['username']);
$envContent = setEnvValue($envContent, 'DB_PASSWORD', $config['password']);

file_put_contents($envPath, $envContent);

echo "Switching to " . strtoupper($mode) . " profile...\n";

$commands = [
    'php artisan config:clear',
    'php artisan route:clear',
    'php artisan view:clear',
    'php artisan event:clear',
];

foreach ($commands as $command) {
    passthru($command, $exitCode);

    if ($exitCode !== 0) {
        fwrite(STDERR, "Warning: command failed: {$command}\n");
    }
}

echo "Done. Active database profile is now [" . strtoupper($mode) . "].\n";
