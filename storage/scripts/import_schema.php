<?php
declare(strict_types=1);

require __DIR__ . '/../config/database.php';

$pdo = Database::getConnection();

$sqlPath = __DIR__ . '/../migrations/create_db.sql';
$sql = file_get_contents($sqlPath);
if ($sql === false) {
    throw new \RuntimeException('Impossibile leggere il file SQL.');
}

$pdo->exec($sql);

echo "Migrazione completata.\n";
