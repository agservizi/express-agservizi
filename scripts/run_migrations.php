<?php
declare(strict_types=1);

require __DIR__ . '/../config/database.php';

$pdo = Database::getConnection();
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

$migrations = [
    __DIR__ . '/../migrations/20251111_update_pda_imports.sql',
    __DIR__ . '/../migrations/20260128_add_audit_log.sql',
    __DIR__ . '/../migrations/20260128_add_energy_contracts.sql',
    __DIR__ . '/../migrations/20260129_add_energy_offers.sql',
    __DIR__ . '/../migrations/20260129_add_energy_sim_requests.sql',
    __DIR__ . '/../migrations/20260129_alter_energy_offers_supply_type.sql',
    __DIR__ . '/../migrations/20260201_add_licenses.sql',
    __DIR__ . '/../migrations/20260202_add_tenants.sql',
    __DIR__ . '/../migrations/20260203_add_user_email.sql',
    __DIR__ . '/../migrations/20260204_add_tenant_scopes.sql',
    __DIR__ . '/../migrations/20260205_add_license_terms_and_renewal_notices.sql',
];

$ignorableErrors = [1060, 1061, 1091, 1050];

foreach ($migrations as $path) {
    if (!is_file($path)) {
        fwrite(STDERR, "File mancante: {$path}\n");
        continue;
    }

    $sql = file_get_contents($path);
    if ($sql === false) {
        fwrite(STDERR, "Impossibile leggere: {$path}\n");
        continue;
    }

    try {
        $pdo->exec($sql);
        echo "OK: {$path}\n";
    } catch (PDOException $exception) {
        $errorInfo = $exception->errorInfo;
        $code = is_array($errorInfo) ? ($errorInfo[1] ?? null) : null;
        if ($code !== null && in_array((int) $code, $ignorableErrors, true)) {
            echo "SKIP ({$code}): {$path}\n";
            continue;
        }
        throw $exception;
    }
}
