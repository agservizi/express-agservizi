<?php

declare(strict_types=1);

require __DIR__ . '/../config/database.php';

$pdo = Database::getConnection();
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

function tableExists(PDO $pdo, string $table): bool
{
    $stmt = $pdo->prepare('SELECT COUNT(*) FROM information_schema.TABLES WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = :table');
    $stmt->execute([':table' => $table]);
    return (int) $stmt->fetchColumn() > 0;
}

function getColumns(PDO $pdo, string $table): array
{
    static $cache = [];
    if (isset($cache[$table])) {
        return $cache[$table];
    }
    $stmt = $pdo->prepare('SELECT COLUMN_NAME FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = :table');
    $stmt->execute([':table' => $table]);
    $columns = $stmt->fetchAll(PDO::FETCH_COLUMN);
    $cache[$table] = array_map('strtolower', $columns ?: []);
    return $cache[$table];
}

function hasColumn(PDO $pdo, string $table, string $column): bool
{
    return in_array(strtolower($column), getColumns($pdo, $table), true);
}

function filterData(PDO $pdo, string $table, array $data): array
{
    $columns = getColumns($pdo, $table);
    $filtered = [];
    foreach ($data as $key => $value) {
        if (in_array(strtolower((string) $key), $columns, true)) {
            $filtered[$key] = $value;
        }
    }
    return $filtered;
}

function insertRow(PDO $pdo, string $table, array $data): int
{
    $data = filterData($pdo, $table, $data);
    if ($data === []) {
        return 0;
    }
    $columns = array_keys($data);
    $placeholders = array_map(static fn($c) => ':' . $c, $columns);
    $sql = sprintf(
        'INSERT INTO %s (%s) VALUES (%s)',
        $table,
        implode(', ', $columns),
        implode(', ', $placeholders)
    );
    $stmt = $pdo->prepare($sql);
    foreach ($data as $key => $value) {
        $stmt->bindValue(':' . $key, $value);
    }
    $stmt->execute();
    return (int) $pdo->lastInsertId();
}

function upsertUser(PDO $pdo, array $data): int
{
    if (!isset($data['username'])) {
        throw new InvalidArgumentException('Username mancante.');
    }
    $stmt = $pdo->prepare('SELECT id FROM users WHERE username = :username LIMIT 1');
    $stmt->execute([':username' => $data['username']]);
    $existingId = (int) $stmt->fetchColumn();
    if ($existingId > 0) {
        $data = filterData($pdo, 'users', $data);
        unset($data['username']);
        if ($data !== []) {
            $sets = [];
            $params = [':id' => $existingId];
            foreach ($data as $key => $value) {
                $sets[] = $key . ' = :' . $key;
                $params[':' . $key] = $value;
            }
            $pdo->prepare('UPDATE users SET ' . implode(', ', $sets) . ' WHERE id = :id')->execute($params);
        }
        return $existingId;
    }

    return insertRow($pdo, 'users', $data);
}

function randomDigits(int $length): string
{
    $digits = '';
    for ($i = 0; $i < $length; $i++) {
        $digits .= (string) random_int(0, 9);
    }
    return $digits;
}

function randomDate(string $start, string $end): string
{
    $min = strtotime($start);
    $max = strtotime($end);
    return date('Y-m-d H:i:s', random_int($min, $max));
}

function randomDateOnly(string $start, string $end): string
{
    $min = strtotime($start);
    $max = strtotime($end);
    return date('Y-m-d', random_int($min, $max));
}

function randomLetter(): string
{
    return chr(random_int(65, 90));
}

function generateFiscalCode(): string
{
    $letters = '';
    for ($i = 0; $i < 6; $i++) {
        $letters .= randomLetter();
    }
    $year = str_pad((string) random_int(60, 99), 2, '0', STR_PAD_LEFT);
    $month = ['A','B','C','D','E','H','L','M','P','R','S','T'][array_rand(['A','B','C','D','E','H','L','M','P','R','S','T'])];
    $day = str_pad((string) random_int(1, 28), 2, '0', STR_PAD_LEFT);
    $city = randomLetter() . str_pad((string) random_int(100, 999), 3, '0', STR_PAD_LEFT);
    $control = randomLetter();

    return $letters . $year . $month . $day . $city . $control;
}

function writeReceiptSettings(int $tenantId, array $headerLines): void
{
    if ($tenantId <= 0) {
        return;
    }

    $baseDir = dirname(__DIR__) . '/storage/config/tenants/tenant_' . $tenantId;
    if (!is_dir($baseDir)) {
        @mkdir($baseDir, 0775, true);
    }

    $path = $baseDir . '/receipt_settings.json';
    $payload = [
        'header_lines' => $headerLines,
        'document_title' => 'DOCUMENTO GESTIONALE',
        'document_number_template' => '{{document_title}} #{{sale_id}}',
        'thanks_text' => 'Grazie per il tuo acquisto!',
        'footer_text' => 'Hai bisogno di stampare di nuovo? Puoi sempre recuperare questo DOCUMENTO GESTIONALE dalla sezione vendite.',
        'labels' => [
            'date' => 'Data',
            'operator' => 'Operatore',
            'customer' => 'Cliente',
            'vat' => 'IVA',
            'vat_included' => 'IVA compresa',
            'vat_codes' => 'Codici IVA applicati',
            'discount' => 'Sconto',
            'total' => 'Totale',
            'total_original' => 'Totale originario',
            'payment' => 'Pagamento',
            'refund_amount' => 'Importo reso',
            'cancelled_at' => 'Annullato il',
            'cancellation_reason' => 'Motivo annullo',
            'refunded_at' => 'Reso registrato il',
            'refund_note' => 'Note reso',
        ],
        'status_labels' => [
            'cancelled' => 'ANNULLATO',
            'refunded' => 'RESO',
        ],
        'configured_at' => date('Y-m-d H:i:s'),
    ];

    $json = json_encode($payload, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    if ($json !== false) {
        file_put_contents($path, $json);
    }
}

function seedDemoTenant(PDO $pdo): void
{
    if (!tableExists($pdo, 'tenants')) {
        throw new RuntimeException('Tabella tenants non trovata.');
    }

    $tenantSlug = 'default';
    $stmt = $pdo->prepare('SELECT id FROM tenants WHERE slug = :slug LIMIT 1');
    $stmt->execute([':slug' => $tenantSlug]);
    $tenantId = (int) $stmt->fetchColumn();

    if ($tenantId === 0) {
        throw new RuntimeException('Tenant default non trovato.');
    } else {
        $updateColumns = [];
        $params = [':id' => $tenantId];
        if (hasColumn($pdo, 'tenants', 'company_name')) {
            $updateColumns[] = 'company_name = :company_name';
            $params[':company_name'] = 'Demo Store S.r.l.';
        }
        if (hasColumn($pdo, 'tenants', 'company_address')) {
            $updateColumns[] = 'company_address = :company_address';
            $params[':company_address'] = 'Via Roma 10, Milano';
        }
        if ($updateColumns !== []) {
            $pdo->prepare('UPDATE tenants SET ' . implode(', ', $updateColumns) . ' WHERE id = :id')->execute($params);
        }
    }

    $stmt = $pdo->prepare('SELECT name FROM tenants WHERE id = :id LIMIT 1');
    $stmt->execute([':id' => $tenantId]);
    $tenantName = (string) $stmt->fetchColumn();
    if ($tenantName === '') {
        $tenantName = 'Default';
    }

    $emailDomain = $tenantSlug . '.coresuite.test';

    writeReceiptSettings($tenantId, [
        'Telefonia Plinio',
        'Via Roma 10, Milano',
        'P.IVA IT12345678901',
    ]);

    $pdo->beginTransaction();

    $demoUsers = [];
    if (tableExists($pdo, 'users') && hasColumn($pdo, 'users', 'tenant_id')) {
        $stmt = $pdo->prepare('SELECT id FROM users WHERE tenant_id = :tenant');
        $stmt->execute([':tenant' => $tenantId]);
        $demoUsers = $stmt->fetchAll(PDO::FETCH_COLUMN);
    }

    $tablesToClear = [
        'sale_item_refunds',
        'sale_items',
        'customer_payments',
        'customer_support_requests',
        'customer_portal_sessions',
        'customer_portal_accounts',
        'privacy_policy_acceptances',
        'pda_imports',
        'system_notifications',
        'energy_contracts',
        'energy_sim_requests',
        'energy_offers',
        'product_stock_alerts',
        'product_stock_movements',
        'stock_alerts',
        'iccid_stock',
        'operator_offers',
        'discount_campaigns',
        'discount_schemes',
        'sales',
        'customer_product_requests',
        'customers',
        'products',
        'providers',
        'energy_providers',
        'audit_log',
        'sso_auth_codes',
        'sso_tokens',
        'sso_clients',
        'user_mfa_recovery_codes',
        'tenant_checkout_requests',
        'tenant_licenses',
        'license_activations',
        'users',
    ];

    foreach ($tablesToClear as $table) {
        if (!tableExists($pdo, $table)) {
            continue;
        }
        if (hasColumn($pdo, $table, 'tenant_id')) {
            $stmt = $pdo->prepare('DELETE FROM ' . $table . ' WHERE tenant_id = :tenant');
            $stmt->execute([':tenant' => $tenantId]);
        }
    }

    if (tableExists($pdo, 'user_remember_tokens') && $demoUsers !== []) {
        $in = implode(',', array_fill(0, count($demoUsers), '?'));
        $stmt = $pdo->prepare('DELETE FROM user_remember_tokens WHERE user_id IN (' . $in . ')');
        $stmt->execute($demoUsers);
    }

    $pdo->commit();

    $pdo->beginTransaction();

    $roleIds = [];
    if (tableExists($pdo, 'roles')) {
        foreach (['admin', 'cassiere'] as $roleName) {
            $stmt = $pdo->prepare('SELECT id FROM roles WHERE name = :name LIMIT 1');
            $stmt->execute([':name' => $roleName]);
            $roleId = (int) $stmt->fetchColumn();
            if ($roleId === 0) {
                $roleId = insertRow($pdo, 'roles', ['name' => $roleName]);
            }
            $roleIds[$roleName] = $roleId;
        }
    }

    $passwordHash = password_hash('Demo123!', PASSWORD_DEFAULT);
    $adminUserId = upsertUser($pdo, [
        'username' => $tenantSlug . '.admin',
        'email' => $tenantSlug . '.admin@' . $emailDomain,
        'password_hash' => $passwordHash,
        'tenant_id' => $tenantId,
        'role_id' => $roleIds['admin'] ?? 1,
        'fullname' => 'Demo Admin',
        'mfa_enabled' => 0,
    ]);
    $cashierUserId = upsertUser($pdo, [
        'username' => $tenantSlug . '.cassiere',
        'email' => $tenantSlug . '.cassiere@' . $emailDomain,
        'password_hash' => $passwordHash,
        'tenant_id' => $tenantId,
        'role_id' => $roleIds['cassiere'] ?? 2,
        'fullname' => 'Demo Cassiere',
        'mfa_enabled' => 0,
    ]);

    $providerNames = [
        ucfirst($tenantSlug) . ' Iliad',
        ucfirst($tenantSlug) . ' WindTre',
        ucfirst($tenantSlug) . ' Fastweb',
        ucfirst($tenantSlug) . ' Sky',
        ucfirst($tenantSlug) . ' Tiscali',
    ];
    $providerIds = [];
    foreach ($providerNames as $name) {
        $providerIds[] = insertRow($pdo, 'providers', [
            'tenant_id' => $tenantId,
            'name' => $name,
            'reorder_threshold' => random_int(10, 30),
            'notes' => 'Provider demo generato automaticamente.',
        ]);
    }

    $productNames = [
        'Smartphone Nova X',
        'Smartphone Zenith Pro',
        'Tablet Air 11',
        'Router WiFi 6',
        'Cuffie Bluetooth Wave',
        'Powerbank 20000mAh',
        'Smartwatch Pulse',
        'Cover Silicone',
        'Caricatore 65W',
        'Cavo USB-C',
    ];

    $productIds = [];
    for ($i = 0; $i < 25; $i++) {
        $name = $productNames[array_rand($productNames)] . ' ' . strtoupper(randomDigits(3));
        $sku = 'DEMO-SKU-' . strtoupper(bin2hex(random_bytes(3)));
        $imei = random_int(0, 1) === 1 ? randomDigits(15) : null;
        $price = random_int(5000, 90000) / 100;
        $taxRate = 22.00;
        $stock = random_int(3, 80);
        $reserved = random_int(0, min(10, $stock));
        $productIds[] = insertRow($pdo, 'products', [
            'tenant_id' => $tenantId,
            'name' => $name,
            'sku' => $sku,
            'imei' => $imei,
            'category' => 'Hardware',
            'price' => $price,
            'tax_rate' => $taxRate,
            'stock_quantity' => $stock,
            'stock_reserved' => $reserved,
            'reorder_threshold' => random_int(5, 15),
            'is_active' => 1,
        ]);
    }

    $iccidIds = [];
    for ($i = 0; $i < 80; $i++) {
        $providerId = $providerIds[array_rand($providerIds)];
        $iccid = '8939' . randomDigits(16);
        $status = ['InStock', 'Reserved', 'Sold'][array_rand(['InStock', 'Reserved', 'Sold'])];
        $iccidIds[] = insertRow($pdo, 'iccid_stock', [
            'tenant_id' => $tenantId,
            'iccid' => $iccid,
            'provider_id' => $providerId,
            'status' => $status,
            'notes' => 'SIM demo',
        ]);
    }

    foreach ($providerIds as $providerId) {
        insertRow($pdo, 'stock_alerts', [
            'tenant_id' => $tenantId,
            'provider_id' => $providerId,
            'current_stock' => random_int(3, 40),
            'threshold' => random_int(10, 20),
            'average_daily_sales' => random_int(1, 8),
            'days_cover' => random_int(1, 12),
            'last_movement' => randomDate('-20 days', 'now'),
            'status' => 'Open',
            'message' => 'Scorte demo in esaurimento.',
        ]);
    }

    $discountSchemeId = insertRow($pdo, 'discount_schemes', [
        'tenant_id' => $tenantId,
        'name' => ucfirst($tenantSlug) . ' Promo Benvenuto',
        'type' => 'Percent',
        'value' => 10.00,
        'description' => 'Sconto demo 10%',
        'is_active' => 1,
    ]);

    $discountCampaignId = insertRow($pdo, 'discount_campaigns', [
        'tenant_id' => $tenantId,
        'name' => ucfirst($tenantSlug) . ' Black Demo Week',
        'description' => 'Campagna demo',
        'type' => 'Percent',
        'value' => 15.00,
        'is_active' => 1,
        'starts_at' => randomDate('-10 days', 'now'),
        'ends_at' => randomDate('now', '+10 days'),
    ]);

    $customerIds = [];
    $firstNames = [
        'Luca', 'Marco', 'Giulia', 'Sara', 'Francesco', 'Chiara', 'Alessandro', 'Martina',
        'Matteo', 'Elena', 'Davide', 'Valentina', 'Simone', 'Laura', 'Paolo', 'Federica',
        'Andrea', 'Silvia', 'Giorgio', 'Ilaria', 'Riccardo', 'Elisa', 'Roberto', 'Marta',
        'Stefano', 'Alice', 'Nicola', 'Beatrice', 'Gabriele', 'Camilla'
    ];
    $lastNames = [
        'Rossi', 'Bianchi', 'Ferrari', 'Russo', 'Esposito', 'Romano', 'Gallo', 'Costa',
        'Fontana', 'Conti', 'Giordano', 'Mancini', 'Rizzo', 'Lombardi', 'Moretti', 'Barbieri',
        'Mariani', 'Santoro', 'Caruso', 'Greco', 'Bruno', 'Marino', 'De Luca', 'Coppola',
        'Vitale', 'Ricci', 'Serra', 'Ferri', 'Pellegrini', 'Fabbri'
    ];
    $customerNotes = [
        'Preferisce contatto via WhatsApp.',
        'Richiede fattura elettronica ogni fine mese.',
        'Cliente storico con più linee attive.',
        'Disponibile solo la mattina.',
        'Ha richiesto preventivo per upgrade fibra.',
        'Pagamenti sempre puntuali.',
        'Preferisce pagamento con carta.',
        'Da ricontattare per rinnovo offerta.',
        'Ha segnalato problemi di copertura in zona.',
        'Vuole attivare SIM aggiuntiva per familiare.'
    ];
    for ($i = 1; $i <= 30; $i++) {
        $first = $firstNames[array_rand($firstNames)];
        $last = $lastNames[array_rand($lastNames)];
        $fullName = $first . ' ' . $last;
        $emailLocal = strtolower($first . '.' . $last . $i);
        $customerIds[] = insertRow($pdo, 'customers', [
            'tenant_id' => $tenantId,
            'fullname' => $fullName,
            'email' => $emailLocal . '@' . $emailDomain,
            'phone' => '+39 3' . randomDigits(9),
            'tax_code' => generateFiscalCode(),
            'note' => $customerNotes[array_rand($customerNotes)],
        ]);
    }

    $portalAccountIds = [];
    foreach (array_slice($customerIds, 0, 15) as $index => $customerId) {
        $portalAccountIds[] = insertRow($pdo, 'customer_portal_accounts', [
            'tenant_id' => $tenantId,
            'customer_id' => $customerId,
            'email' => 'portal' . ($index + 1) . '@' . $emailDomain,
            'password_hash' => $passwordHash,
            'invite_token' => bin2hex(random_bytes(16)),
            'invite_sent_at' => randomDate('-15 days', '-5 days'),
            'last_login_at' => randomDate('-4 days', 'now'),
        ]);
    }

    foreach ($portalAccountIds as $portalAccountId) {
        insertRow($pdo, 'customer_portal_sessions', [
            'tenant_id' => $tenantId,
            'portal_account_id' => $portalAccountId,
            'session_token' => hash('sha256', random_bytes(16)),
            'expires_at' => randomDate('now', '+14 days'),
            'user_agent' => 'Mozilla/5.0',
            'ip_address' => '192.168.1.' . random_int(10, 250),
        ]);
    }

    foreach (array_slice($portalAccountIds, 0, 8) as $portalAccountId) {
        $customerId = $customerIds[array_rand($customerIds)];
        insertRow($pdo, 'customer_support_requests', [
            'tenant_id' => $tenantId,
            'customer_id' => $customerId,
            'portal_account_id' => $portalAccountId,
            'type' => 'Support',
            'subject' => 'Richiesta demo',
            'message' => 'Serve supporto per il portale demo.',
            'preferred_slot' => randomDate('+1 day', '+10 days'),
            'status' => 'InProgress',
        ]);
    }

    foreach (array_slice($portalAccountIds, 0, 10) as $portalAccountId) {
        $customerId = $customerIds[array_rand($customerIds)];
        $productId = $productIds[array_rand($productIds)];
        insertRow($pdo, 'customer_product_requests', [
            'tenant_id' => $tenantId,
            'customer_id' => $customerId,
            'portal_account_id' => $portalAccountId,
            'product_id' => $productId,
            'product_name' => 'Prodotto demo',
            'product_price' => random_int(50, 500),
            'request_type' => 'Purchase',
            'status' => 'Pending',
            'deposit_amount' => random_int(10, 50),
            'installments' => random_int(3, 12),
            'payment_method' => 'BankTransfer',
            'desired_pickup_date' => randomDateOnly('now', '+14 days'),
        ]);
    }

    $sales = [];
    for ($i = 0; $i < 40; $i++) {
        $customerId = $customerIds[array_rand($customerIds)];
        $userId = random_int(0, 1) === 1 ? $adminUserId : $cashierUserId;
        $createdAt = randomDate('-25 days', 'now');
        $status = ['Completed', 'Completed', 'Cancelled'][array_rand(['Completed', 'Completed', 'Cancelled'])];
        $saleId = insertRow($pdo, 'sales', [
            'tenant_id' => $tenantId,
            'user_id' => $userId,
            'customer_id' => $customerId,
            'customer_name' => 'Cliente Demo',
            'total' => 0,
            'vat' => 22.00,
            'discount' => random_int(0, 1) ? 0 : 5.00,
            'discount_campaign_id' => random_int(0, 1) ? $discountCampaignId : null,
            'payment_method' => ['Contanti', 'Carta', 'POS'][array_rand(['Contanti', 'Carta', 'POS'])],
            'status' => $status,
            'created_at' => $createdAt,
        ]);
        $sales[] = $saleId;

        $itemCount = random_int(1, 3);
        $total = 0.0;
        for ($j = 0; $j < $itemCount; $j++) {
            $useIccid = random_int(0, 1) === 1;
            $productId = $productIds[array_rand($productIds)];
            $price = random_int(1000, 90000) / 100;
            $quantity = random_int(1, 2);
            $total += $price * $quantity;

            insertRow($pdo, 'sale_items', [
                'tenant_id' => $tenantId,
                'sale_id' => $saleId,
                'iccid_id' => $useIccid ? $iccidIds[array_rand($iccidIds)] : null,
                'product_id' => $useIccid ? null : $productId,
                'product_imei' => $useIccid ? null : randomDigits(15),
                'description' => $useIccid ? 'SIM demo' : 'Prodotto demo',
                'quantity' => $quantity,
                'price' => $price,
                'tax_rate' => 22.00,
                'tax_amount' => round($price * $quantity * 0.22, 4),
            ]);
        }

        $totalPaid = $status === 'Completed' ? $total : 0.00;
        $balanceDue = $status === 'Completed' ? 0.00 : $total;
        $paymentStatus = $status === 'Completed' ? 'Paid' : 'Pending';

        $updateData = ['total' => $total];
        if (hasColumn($pdo, 'sales', 'total_paid')) {
            $updateData['total_paid'] = $totalPaid;
        }
        if (hasColumn($pdo, 'sales', 'balance_due')) {
            $updateData['balance_due'] = $balanceDue;
        }
        if (hasColumn($pdo, 'sales', 'payment_status')) {
            $updateData['payment_status'] = $paymentStatus;
        }
        if (hasColumn($pdo, 'sales', 'due_date') && $balanceDue > 0) {
            $updateData['due_date'] = randomDateOnly('now', '+15 days');
        }

        if ($updateData !== []) {
            $sets = [];
            $params = [':id' => $saleId];
            foreach ($updateData as $key => $value) {
                $sets[] = $key . ' = :' . $key;
                $params[':' . $key] = $value;
            }
            $pdo->prepare('UPDATE sales SET ' . implode(', ', $sets) . ' WHERE id = :id')->execute($params);
        }
    }

    foreach (array_slice($sales, 0, 10) as $saleId) {
        $portalAccountId = $portalAccountIds[array_rand($portalAccountIds)];
        insertRow($pdo, 'customer_payments', [
            'tenant_id' => $tenantId,
            'sale_id' => $saleId,
            'portal_account_id' => $portalAccountId,
            'amount' => random_int(50, 500),
            'payment_method' => 'Card',
            'status' => 'Succeeded',
            'provider_reference' => 'PAY-' . strtoupper(bin2hex(random_bytes(4))),
        ]);
    }

    $saleItemRefundIds = [];
    if (tableExists($pdo, 'sale_items')) {
        $stmt = $pdo->prepare('SELECT id FROM sale_items WHERE tenant_id = :tenant LIMIT 10');
        $stmt->execute([':tenant' => $tenantId]);
        $saleItemRefundIds = $stmt->fetchAll(PDO::FETCH_COLUMN);
    }

    foreach ($saleItemRefundIds as $saleItemId) {
        insertRow($pdo, 'sale_item_refunds', [
            'tenant_id' => $tenantId,
            'sale_item_id' => $saleItemId,
            'user_id' => $adminUserId,
            'quantity' => 1,
            'refund_type' => 'Refund',
            'note' => 'Rimborso demo',
            'amount' => random_int(10, 150),
        ]);
    }

    foreach (array_slice($productIds, 0, 10) as $productId) {
        insertRow($pdo, 'product_stock_movements', [
            'tenant_id' => $tenantId,
            'product_id' => $productId,
            'quantity_change' => random_int(-5, 15),
            'balance_after' => random_int(1, 100),
            'reason' => 'demo_seed',
            'reference_type' => 'sale',
            'reference_id' => $sales[array_rand($sales)],
            'user_id' => $adminUserId,
            'note' => 'Movimento demo',
        ]);
    }

    foreach (array_slice($productIds, 0, 5) as $productId) {
        insertRow($pdo, 'product_stock_alerts', [
            'tenant_id' => $tenantId,
            'product_id' => $productId,
            'current_stock' => random_int(1, 5),
            'stock_reserved' => random_int(0, 2),
            'threshold' => 6,
            'average_daily_sales' => random_int(1, 4),
            'days_cover' => random_int(1, 6),
            'last_movement' => randomDate('-10 days', 'now'),
            'status' => 'Open',
            'message' => 'Stock prodotto demo basso.',
        ]);
    }

    insertRow($pdo, 'audit_log', [
        'tenant_id' => $tenantId,
        'user_id' => $adminUserId,
        'action' => 'demo_seed',
        'description' => 'Popolamento dati demo.',
    ]);

    foreach (['Attivazione SIM Premium', 'Promo Smart Home', 'Upgrade Fibra'] as $offerTitle) {
        insertRow($pdo, 'operator_offers', [
            'tenant_id' => $tenantId,
            'provider_id' => $providerIds[array_rand($providerIds)],
            'title' => $offerTitle,
            'description' => 'Offerta demo generata automaticamente.',
            'price' => random_int(5, 30),
            'status' => 'Active',
            'valid_from' => randomDateOnly('-20 days', 'now'),
            'valid_to' => randomDateOnly('now', '+30 days'),
        ]);
    }

    $energyProviderIds = [];
    if (tableExists($pdo, 'energy_providers')) {
        foreach ([ucfirst($tenantSlug) . ' Energia', ucfirst($tenantSlug) . ' Green Power'] as $name) {
            $energyProviderIds[] = insertRow($pdo, 'energy_providers', [
                'tenant_id' => $tenantId,
                'name' => $name,
                'service_type' => 'luce_gas',
                'token_luce' => 25.00,
                'token_gas' => 20.00,
                'notes' => 'Provider energia demo.',
            ]);
        }
    }

    if (tableExists($pdo, 'energy_offers')) {
        for ($i = 1; $i <= 6; $i++) {
            $energyProviderId = $energyProviderIds !== [] ? $energyProviderIds[array_rand($energyProviderIds)] : null;
            insertRow($pdo, 'energy_offers', [
                'tenant_id' => $tenantId,
                'provider_id' => $energyProviderId,
                'provider_name' => $energyProviderId ? 'Demo Energia' : 'Demo Partner',
                'offer_code' => 'DEMO-OFF-' . $i . '-' . randomDigits(3),
                'offer_name' => 'Offerta Energia Demo ' . $i,
                'supply_type' => random_int(0, 1) ? 'luce' : 'gas',
                'customer_type' => 'domestico',
                'offer_type' => 'fisso',
                'price_type' => 'mono',
                'p_fix_f' => random_int(50, 150) / 10,
                'p_fix_v' => random_int(10, 60) / 100,
                'region' => 'Lombardia',
                'province' => 'MI',
                'municipality' => 'Milano',
                'offer_url' => 'https://' . $emailDomain . '/energia/' . $i,
                'valid_from' => randomDateOnly('-30 days', 'now'),
                'valid_to' => randomDateOnly('now', '+90 days'),
                'source' => 'demo',
            ]);
        }
    }

    if (tableExists($pdo, 'energy_contracts')) {
        foreach (array_slice($customerIds, 0, 10) as $customerId) {
            insertRow($pdo, 'energy_contracts', [
                'tenant_id' => $tenantId,
                'customer_id' => $customerId,
                'customer_name' => 'Cliente Energia Demo',
                'contract_type' => 'luce_gas',
                'provider_id' => $energyProviderIds[array_rand($energyProviderIds)],
                'token_value' => random_int(20, 50),
                'user_id' => $adminUserId,
                'notes' => 'Contratto energia demo.',
            ]);
        }
    }

    if (tableExists($pdo, 'energy_sim_requests')) {
        foreach (array_slice($customerIds, 0, 8) as $customerId) {
            insertRow($pdo, 'energy_sim_requests', [
                'tenant_id' => $tenantId,
                'request_type' => 'call_back',
                'contact_name' => 'Richiesta Demo',
                'contact_email' => 'richiesta@' . $emailDomain,
                'contact_phone' => '+39 02 5555 1234',
                'preferred_time' => 'Pomeriggio',
                'contact_note' => 'Richiesta demo simulata.',
                'sim_summary' => 'Preventivo demo',
                'user_id' => $adminUserId,
            ]);
        }
    }

    if (tableExists($pdo, 'system_notifications')) {
        for ($i = 1; $i <= 8; $i++) {
            insertRow($pdo, 'system_notifications', [
                'tenant_id' => $tenantId,
                'type' => 'demo',
                'title' => 'Notifica demo #' . $i,
                'body' => 'Messaggio demo generato automaticamente.',
                'level' => ['info', 'success', 'warning'][array_rand(['info', 'success', 'warning'])],
                'channel' => 'system',
                'source' => 'seed',
                'recipient_user_id' => $adminUserId,
                'is_read' => random_int(0, 1),
            ]);
        }
    }

    if (tableExists($pdo, 'pda_imports')) {
        for ($i = 1; $i <= 5; $i++) {
            insertRow($pdo, 'pda_imports', [
                'tenant_id' => $tenantId,
                'user_id' => $adminUserId,
                'provider_id' => $providerIds[array_rand($providerIds)],
                'provider_name' => 'Demo Provider',
                'source_filename' => 'demo_import_' . $i . '.pdf',
                'stored_path' => '/storage/pda/demo_import_' . $i . '.pdf',
                'status' => 'Processed',
                'customer_id' => $customerIds[array_rand($customerIds)],
                'notes' => 'Import demo',
            ]);
        }
    }

    if (tableExists($pdo, 'privacy_policy_acceptances')) {
        $stmt = $pdo->query('SELECT id FROM privacy_policies ORDER BY id ASC LIMIT 1');
        $policyId = (int) $stmt->fetchColumn();
        if ($policyId > 0) {
            foreach (array_slice($portalAccountIds, 0, 5) as $portalAccountId) {
                insertRow($pdo, 'privacy_policy_acceptances', [
                    'tenant_id' => $tenantId,
                    'portal_account_id' => $portalAccountId,
                    'policy_id' => $policyId,
                    'ip_address' => '192.168.1.' . random_int(1, 200),
                    'user_agent' => 'Mozilla/5.0',
                ]);
            }
        }
    }

    if (tableExists($pdo, 'sso_clients')) {
        $clientSecret = bin2hex(random_bytes(16));
        $clientIdValue = bin2hex(random_bytes(16));
        $clientId = insertRow($pdo, 'sso_clients', [
            'tenant_id' => $tenantId,
            'name' => 'Demo SSO App',
            'client_id' => $clientIdValue,
            'client_secret_hash' => hash('sha256', $clientSecret),
            'redirect_uri' => 'https://' . $emailDomain . '/sso/callback',
            'is_active' => 1,
            'is_confidential' => 1,
        ]);

        $authCodeHash = hash('sha256', bin2hex(random_bytes(16)));
        $authCodeId = insertRow($pdo, 'sso_auth_codes', [
            'tenant_id' => $tenantId,
            'client_id' => $clientId,
            'user_id' => $adminUserId,
            'code_hash' => $authCodeHash,
            'code_challenge' => null,
            'code_method' => 'plain',
            'redirect_uri' => 'https://' . $emailDomain . '/sso/callback',
            'state' => 'demo',
            'expires_at' => randomDate('now', '+1 day'),
        ]);

        insertRow($pdo, 'sso_tokens', [
            'tenant_id' => $tenantId,
            'client_id' => $clientId,
            'user_id' => $adminUserId,
            'access_token_hash' => hash('sha256', bin2hex(random_bytes(16))),
            'refresh_token_hash' => hash('sha256', bin2hex(random_bytes(16))),
            'scope' => 'basic',
            'expires_at' => randomDate('now', '+30 days'),
        ]);
    }

    if (tableExists($pdo, 'user_mfa_recovery_codes')) {
        for ($i = 0; $i < 3; $i++) {
            insertRow($pdo, 'user_mfa_recovery_codes', [
                'tenant_id' => $tenantId,
                'user_id' => $adminUserId,
                'code_hash' => hash('sha256', bin2hex(random_bytes(8))),
                'used_at' => null,
            ]);
        }
    }

    if (tableExists($pdo, 'licenses')) {
        $stmt = $pdo->query('SELECT id FROM licenses ORDER BY id ASC LIMIT 1');
        $licenseId = (int) $stmt->fetchColumn();
        if ($licenseId === 0) {
            $licenseId = insertRow($pdo, 'licenses', [
                'code' => 'DEMO-LIC-' . strtoupper(bin2hex(random_bytes(3))),
                'label' => 'Licenza Demo',
                'max_users' => 4,
                'term_months' => 12,
                'is_active' => 1,
                'expires_at' => randomDateOnly('now', '+365 days'),
            ]);
        }

        insertRow($pdo, 'tenant_licenses', [
            'tenant_id' => $tenantId,
            'license_id' => $licenseId,
            'max_users_override' => 4,
            'notes' => 'Licenza assegnata al tenant demo.',
        ]);

        insertRow($pdo, 'license_activations', [
            'tenant_id' => $tenantId,
            'license_id' => $licenseId,
            'notes' => 'Attivazione demo.',
        ]);
    }

    if (tableExists($pdo, 'tenant_checkout_requests')) {
        insertRow($pdo, 'tenant_checkout_requests', [
            'tenant_id' => $tenantId,
            'plan_key' => 'demo',
            'tenant_name' => $tenantName,
            'tenant_slug' => $tenantSlug,
            'tenant_email' => $tenantSlug . '@' . $emailDomain,
            'tenant_phone' => '+39 02 1234 5678',
            'status' => 'paid',
            'stripe_session_id' => 'cs_demo_' . bin2hex(random_bytes(4)),
            'stripe_payment_intent_id' => 'pi_demo_' . bin2hex(random_bytes(4)),
            'stripe_customer_email' => 'demo@coresuite.test',
            'vat_number' => 'IT' . randomDigits(11),
            'company_country' => 'IT',
            'company_name' => 'Demo Store S.r.l.',
            'company_address' => 'Via Roma 10, Milano',
            'paid_at' => randomDate('-5 days', 'now'),
        ]);
    }

    $pdo->commit();

    echo "Demo tenant seed completato. Tenant ID: {$tenantId}\n";
}

seedDemoTenant($pdo);
