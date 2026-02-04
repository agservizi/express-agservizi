<?php
declare(strict_types=1);

$autoloadPath = __DIR__ . '/../vendor/autoload.php';
if (is_file($autoloadPath)) {
    require_once $autoloadPath;
}

$config = require __DIR__ . '/config.php';
$GLOBALS['config'] = $config;
$timezone = $config['app']['timezone'] ?? 'Europe/Rome';
if (is_string($timezone) && $timezone !== '') {
    try {
        new \DateTimeZone($timezone);
        date_default_timezone_set($timezone);
    } catch (\Throwable $exception) {
        date_default_timezone_set('Europe/Rome');
    }
}
$activeConfig = $GLOBALS['db_config'] ?? $config['db'];
if (!isset($activeConfig['timezone']) || !is_string($activeConfig['timezone']) || $activeConfig['timezone'] === '') {
    $activeConfig['timezone'] = $timezone;
}

final class Database
{
    private static ?PDO $pdo = null;
    private static ?PDO $serverPdo = null;
    private static ?string $timezone = null;

    public static function configure(array $settings): void
    {
        $tz = $settings['timezone'] ?? null;
        self::$timezone = is_string($tz) && $tz !== '' ? $tz : null;
    }

    private static function getEnv(string $key): ?string
    {
        $value = getenv($key);
        if ($value !== false && $value !== '') {
            return $value;
        }
        $envValue = $_ENV[$key] ?? null;
        return is_string($envValue) && $envValue !== '' ? $envValue : null;
    }

    public static function getConnection(): PDO
    {
        if (self::$pdo === null) {
            $host = self::getEnv('DB_HOST');
            $port = self::getEnv('DB_PORT') ?: '3306';
            $name = self::getEnv('DB_NAME');
            $user = self::getEnv('DB_USER');
            $pass = self::getEnv('DB_PASS') ?? '';

            if ($host === null || $name === null || $user === null) {
                throw new RuntimeException('Variabili DB_HOST/DB_NAME/DB_USER mancanti.');
            }

            $dsn = sprintf('mysql:host=%s;port=%s;dbname=%s;charset=utf8mb4', $host, $port, $name);
            $options = [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_PERSISTENT => false,
            ];

            self::$pdo = new PDO($dsn, $user, $pass, $options);

            if (self::$timezone !== null) {
                try {
                    $tz = new DateTimeZone(self::$timezone);
                    $timezoneName = $tz->getName();
                    $quotedName = self::$pdo->quote($timezoneName);
                    self::$pdo->exec('SET time_zone = ' . $quotedName);
                } catch (\Throwable $exception) {
                    try {
                        $tz = new DateTimeZone(self::$timezone);
                        $now = new DateTimeImmutable('now', $tz);
                        $offsetSeconds = $tz->getOffset($now);
                        $sign = $offsetSeconds >= 0 ? '+' : '-';
                        $offsetSeconds = abs($offsetSeconds);
                        $hours = intdiv($offsetSeconds, 3600);
                        $minutes = intdiv($offsetSeconds % 3600, 60);
                        $formattedOffset = sprintf('%s%02d:%02d', $sign, $hours, $minutes);
                        self::$pdo->exec('SET time_zone = ' . self::$pdo->quote($formattedOffset));
                    } catch (\Throwable $innerException) {
                        // Ignora se non è possibile impostare il fuso orario sul database.
                    }
                }
            }
        }

        return self::$pdo;
    }

    public static function getServerConnection(): PDO
    {
        if (self::$serverPdo === null) {
            $host = self::getEnv('DB_HOST');
            $port = self::getEnv('DB_PORT') ?: '3306';
            $user = self::getEnv('DB_USER');
            $pass = self::getEnv('DB_PASS') ?? '';

            if ($host === null || $user === null) {
                throw new RuntimeException('Variabili DB_HOST/DB_USER mancanti.');
            }

            $dsn = sprintf('mysql:host=%s;port=%s;charset=utf8mb4', $host, $port);
            $options = [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_PERSISTENT => false,
            ];

            self::$serverPdo = new PDO($dsn, $user, $pass, $options);
        }

        return self::$serverPdo;
    }

    public static function reset(): void
    {
        self::$pdo = null;
        self::$serverPdo = null;
    }
}

Database::configure($activeConfig);
