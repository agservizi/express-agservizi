<?php
declare(strict_types=1);

if ($argc < 2 || !is_string($argv[1]) || $argv[1] === '') {
    fwrite(STDERR, "Uso: php update_admin_password.php <nuova_password>\n");
    exit(1);
}

require __DIR__ . '/../config/database.php';

$password = $argv[1];

$pdo = Database::getConnection();
$hash = password_hash($password, PASSWORD_DEFAULT);
if ($hash === false) {
    fwrite(STDERR, "Errore: impossibile generare hash password.\n");
    exit(1);
}

$stmt = $pdo->prepare('UPDATE users SET password_hash = :hash, mfa_enabled = 0, mfa_secret = NULL, mfa_enabled_at = NULL WHERE username = :u');
$stmt->execute([
    ':hash' => $hash,
    ':u' => 'admin',
]);

echo "Password admin aggiornata.\n";
