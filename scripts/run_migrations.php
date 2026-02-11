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
    __DIR__ . '/../migrations/20260201_update_privacy_policy_contacts.sql',
    __DIR__ . '/../migrations/20260201_fix_privacy_policy_tenant.sql',
    __DIR__ . '/../migrations/20260202_add_tenants.sql',
    __DIR__ . '/../migrations/20260203_add_user_email.sql',
    __DIR__ . '/../migrations/20260204_add_tenant_scopes.sql',
    __DIR__ . '/../migrations/20260205_add_license_terms_and_renewal_notices.sql',
    __DIR__ . '/../migrations/20260206_add_tenant_checkout_requests.sql',
    __DIR__ . '/../migrations/20260207_add_tenant_vat_details.sql',
    __DIR__ . '/../migrations/20260208_add_checkout_billing_cycle.sql',
];

$ignorableErrors = [1060, 1061, 1091, 1050, 121];

function listExistingTables(PDO $pdo): array
{
    $tables = [];
    $stmt = $pdo->query('SELECT TABLE_NAME FROM information_schema.TABLES WHERE TABLE_SCHEMA = DATABASE()');
    if ($stmt === false) {
        return $tables;
    }

    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        if (!empty($row['TABLE_NAME'])) {
            $tables[(string) $row['TABLE_NAME']] = true;
        }
    }

    return $tables;
}

function splitSqlStatements(string $sql): array
{
    $lines = preg_split('/;\s*(?:\r?\n|$)/', $sql);
    return $lines === false ? [] : $lines;
}

function extractTableName(string $statement, string $type): ?string
{
    $pattern = match (strtolower($type)) {
        'create' => '/^\s*CREATE\s+TABLE\s+(?:IF\s+NOT\s+EXISTS\s+)?(?:`?[\w]+`?\.)?(`?[\w]+`?)/i',
        'alter' => '/^\s*ALTER\s+TABLE\s+(?:`?[\w]+`?\.)?(`?[\w]+`?)/i',
        'drop' => '/^\s*DROP\s+TABLE\s+(?:IF\s+EXISTS\s+)?(?:`?[\w]+`?\.)?(`?[\w]+`?)/i',
        default => null,
    };

    if ($pattern === null || !preg_match($pattern, $statement, $matches)) {
        return null;
    }

    $name = $matches[1] ?? '';
    $name = trim($name, "`\"");

    return $name !== '' ? $name : null;
}

function execStatement(PDO $pdo, string $statement, array $ignorableErrors): bool
{
    try {
        $pdo->exec($statement);
        return true;
    } catch (PDOException $exception) {
        $errorInfo = $exception->errorInfo;
        $code = is_array($errorInfo) ? ($errorInfo[1] ?? null) : null;
        $message = $exception->getMessage();
        $isDuplicateKey = $code === 1005 && preg_match('/errno:\s*121\b/i', $message) === 1;
        if ($isDuplicateKey || ($code !== null && in_array((int) $code, $ignorableErrors, true))) {
            return false;
        }
        throw $exception;
    }
}

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

    $statements = splitSqlStatements($sql);
    $existingTables = listExistingTables($pdo);
    $executed = 0;
    $skipped = 0;

    foreach ($statements as $statement) {
        $trimmed = trim($statement);
        if ($trimmed === '') {
            continue;
        }

        $createTable = extractTableName($trimmed, 'create');
        if ($createTable !== null) {
            if (isset($existingTables[$createTable])) {
                $skipped++;
                continue;
            }
            if (execStatement($pdo, $trimmed, $ignorableErrors)) {
                $existingTables[$createTable] = true;
                $executed++;
            } else {
                $skipped++;
            }
            continue;
        }

        $alterTable = extractTableName($trimmed, 'alter');
        if ($alterTable !== null) {
            if (!isset($existingTables[$alterTable])) {
                $skipped++;
                continue;
            }
            if (execStatement($pdo, $trimmed, $ignorableErrors)) {
                $executed++;
            } else {
                $skipped++;
            }
            continue;
        }

        $dropTable = extractTableName($trimmed, 'drop');
        if ($dropTable !== null) {
            if (!isset($existingTables[$dropTable])) {
                $skipped++;
                continue;
            }
            if (execStatement($pdo, $trimmed, $ignorableErrors)) {
                unset($existingTables[$dropTable]);
                $executed++;
            } else {
                $skipped++;
            }
            continue;
        }

        if (execStatement($pdo, $trimmed, $ignorableErrors)) {
            $executed++;
        } else {
            $skipped++;
        }
    }

    echo "OK: {$path} (eseguiti {$executed}, saltati {$skipped})\n";
}
