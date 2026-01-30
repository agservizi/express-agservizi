<?php
declare(strict_types=1);

require __DIR__ . '/../config/database.php';

$config = $GLOBALS['config']['db'];

$dsn = $config['dsn'];
$dbName = null;
if (preg_match('/dbname=([^;]+)/', $dsn, $matches)) {
    $dbName = $matches[1];
}
$dsn = preg_replace('/dbname=[^;]+;?/', '', $dsn);
if ($dsn === null) {
    throw new \RuntimeException('Impossibile elaborare il DSN.');
}
if (!str_contains($dsn, 'charset=')) {
    $dsn .= (str_ends_with($dsn, ';') ? '' : ';') . 'charset=utf8mb4';
}

$options = $config['options'] ?? [];
$options[\PDO::ATTR_ERRMODE] = \PDO::ERRMODE_EXCEPTION;
if (class_exists('Pdo\\Mysql') && defined('Pdo\\Mysql::ATTR_MULTI_STATEMENTS')) {
    $options[\Pdo\Mysql::ATTR_MULTI_STATEMENTS] = true;
} elseif (defined('PDO::MYSQL_ATTR_MULTI_STATEMENTS')) {
    $options[\PDO::MYSQL_ATTR_MULTI_STATEMENTS] = true;
}

$pdo = new \PDO($dsn, $config['user'], $config['pass'], $options);

if ($dbName !== null && $dbName !== '') {
    $pdo->exec('CREATE DATABASE IF NOT EXISTS `' . str_replace('`', '``', $dbName) . '`');
    $pdo->exec('USE `' . str_replace('`', '``', $dbName) . '`');
}

$sqlPath = $argv[1] ?? (__DIR__ . '/../migrations/create_db.sql');
if (!is_string($sqlPath) || $sqlPath === '') {
    throw new \RuntimeException('Percorso SQL non valido.');
}
$sql = file_get_contents($sqlPath);
if ($sql === false) {
    throw new \RuntimeException('Impossibile leggere il file SQL.');
}

$pdo->exec($sql);

echo "Migrazione completata.\n";
