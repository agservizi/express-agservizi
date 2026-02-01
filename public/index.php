<?php
declare(strict_types=1);

$debugEnabled = isset($_GET['debug']) && $_GET['debug'] === '1';
if ($debugEnabled) {
    ini_set('display_errors', '1');
    ini_set('display_startup_errors', '1');
    error_reporting(E_ALL);
} else {
    ini_set('display_errors', '0');
    ini_set('display_startup_errors', '0');
}

$logDir = __DIR__ . '/../storage/logs';
if (!is_dir($logDir)) {
    @mkdir($logDir, 0775, true);
}
ini_set('log_errors', '1');
ini_set('error_log', $logDir . '/php_errors.log');

session_start();

$autoloadPath = dirname(__DIR__) . '/vendor/autoload.php';
if (is_file($autoloadPath)) {
    require_once $autoloadPath;
}

/**
 * @param array<string, mixed>|string $toast
 */
function pushFlashToast($toast): void
{
    $normalized = normalizeToastPayload($toast);
    if ($normalized === null) {
        return;
    }
    if (!isset($_SESSION['flash_toasts']) || !is_array($_SESSION['flash_toasts'])) {
        $_SESSION['flash_toasts'] = [];
    }
    $_SESSION['flash_toasts'][] = $normalized;
}

/**
 * @return array<int, array<string, mixed>>
 */
function pullFlashToasts(): array
{
    $queued = $_SESSION['flash_toasts'] ?? [];
    unset($_SESSION['flash_toasts']);
    if (!is_array($queued)) {
        return [];
    }

    $result = [];
    foreach ($queued as $toast) {
        $normalized = normalizeToastPayload($toast);
        if ($normalized !== null) {
            $result[] = $normalized;
        }
    }
    return $result;
}

/**
 * @param array<string, mixed>|string $toast
 */
function normalizeToastPayload($toast): ?array
{
    if (is_string($toast)) {
        $message = trim($toast);
        if ($message === '') {
            return null;
        }
        return [
            'type' => 'info',
            'title' => '',
            'message' => $message,
            'duration' => 5000,
            'dismissible' => true,
        ];
    }

    if (!is_array($toast)) {
        return null;
    }

    $message = isset($toast['message']) ? trim((string) $toast['message']) : '';
    if ($message === '') {
        return null;
    }

    $normalized = [
        'type' => isset($toast['type']) ? (string) $toast['type'] : 'info',
        'title' => isset($toast['title']) ? trim((string) $toast['title']) : '',
        'message' => $message,
        'duration' => isset($toast['duration']) && is_numeric($toast['duration']) ? max(0, (int) $toast['duration']) : 5000,
        'dismissible' => !isset($toast['dismissible']) || (bool) $toast['dismissible'],
    ];

    foreach (['id', 'onDismiss', 'meta'] as $optionalKey) {
        if (array_key_exists($optionalKey, $toast)) {
            $normalized[$optionalKey] = $toast[$optionalKey];
        }
    }

    return $normalized;
}

/**
 * @param array<string, mixed> $feedback
 * @param array<string, mixed> $overrides
 * @return array<string, mixed>|null
 */
function toastFromFeedback(array $feedback, array $overrides = []): ?array
{
    $isSuccess = (bool) ($feedback['success'] ?? false);

    $parts = [];
    if (isset($feedback['message'])) {
        $parts[] = trim((string) $feedback['message']);
    }

    if (!$isSuccess) {
        if (!empty($feedback['error'])) {
            $parts[] = 'Dettaglio: ' . trim((string) $feedback['error']);
        }
        if (!empty($feedback['errors']) && is_array($feedback['errors'])) {
            foreach ($feedback['errors'] as $error) {
                $errorText = trim((string) $error);
                if ($errorText !== '') {
                    $parts[] = $errorText;
                }
            }
        }
    }

    $message = trim(implode("\n", array_filter($parts, static fn (string $value): bool => $value !== '')));
    if ($message === '') {
        $message = $isSuccess ? 'Operazione completata.' : 'Impossibile completare l\'operazione.';
    }

    $toast = [
        'type' => $isSuccess ? 'success' : 'danger',
        'title' => $isSuccess ? 'Operazione completata' : 'Operazione non riuscita',
        'message' => $message,
        'duration' => $isSuccess ? 5000 : 0,
        'dismissible' => true,
    ];

    foreach ($overrides as $key => $value) {
        if ($key === 'message' && is_string($value)) {
            $toast[$key] = trim($value);
            continue;
        }
        $toast[$key] = $value;
    }

    return normalizeToastPayload($toast);
}

/**
 * @param array<string, mixed> $payload
 */
function jsonResponse(array $payload, int $status = 200): void
{
    http_response_code($status);
    header('Content-Type: application/json; charset=utf-8');
    $encoded = json_encode(
        $payload,
        JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_INVALID_UTF8_SUBSTITUTE
    );
    if ($encoded === false) {
        $fallback = json_encode(
            [
                'success' => false,
                'error' => 'json_encode_failed',
                'message' => json_last_error_msg(),
            ],
            JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES
        );
        echo $fallback !== false ? $fallback : '{"success":false,"error":"json_encode_failed"}';
        exit;
    }
    echo $encoded;
    exit;
}

function getEnergyOffersImportPaths(): array
{
    $root = dirname(__DIR__);
    $storage = $root . '/storage';
    return [
        'script' => $root . '/scripts/import_energy_offers.php',
        'status' => $storage . '/energy_offers_import_status.json',
        'log' => $storage . '/energy_offers_import.log',
        'storage' => $storage,
    ];
}

/**
 * @return array{last_run?:int,last_started?:int,last_status?:string,last_message?:string,last_output?:string}
 */
function loadEnergyOffersImportStatus(): array
{
    $paths = getEnergyOffersImportPaths();
    if (!is_file($paths['status'])) {
        return [];
    }
    $raw = file_get_contents($paths['status']);
    if ($raw === false) {
        return [];
    }
    $decoded = json_decode($raw, true);
    return is_array($decoded) ? $decoded : [];
}

/**
 * @param array{last_run?:int,last_started?:int,last_status?:string,last_message?:string,last_output?:string} $status
 */
function saveEnergyOffersImportStatus(array $status): void
{
    $paths = getEnergyOffersImportPaths();
    if (!is_dir($paths['storage'])) {
        mkdir($paths['storage'], 0775, true);
    }
    file_put_contents(
        $paths['status'],
        json_encode($status, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)
    );
}

/**
 * @return array{success:bool,message:string,output?:string}
 */
function runEnergyOffersImport(bool $background = false): array
{
    $paths = getEnergyOffersImportPaths();
    $command = escapeshellarg(PHP_BINARY) . ' ' . escapeshellarg($paths['script']);
    $status = loadEnergyOffersImportStatus();

    if ($background) {
        $status['last_started'] = time();
        $status['last_status'] = 'scheduled';
        $status['last_message'] = 'Import programmato in background.';
        saveEnergyOffersImportStatus($status);
        exec($command . ' > ' . escapeshellarg($paths['log']) . ' 2>&1 &');
        return [
            'success' => true,
            'message' => 'Import programmato in background.',
        ];
    }

    $output = [];
    $exitCode = 0;
    exec($command . ' 2>&1', $output, $exitCode);
    $status['last_run'] = time();
    $status['last_status'] = $exitCode === 0 ? 'success' : 'error';
    $status['last_message'] = $exitCode === 0 ? 'Import completato.' : 'Import fallito.';
    $status['last_output'] = implode("\n", array_slice($output, -20));
    saveEnergyOffersImportStatus($status);

    return [
        'success' => $exitCode === 0,
        'message' => $status['last_message'] ?? 'Import completato.',
        'output' => $status['last_output'] ?? null,
    ];
}

function maybeScheduleEnergyOffersImport(int $intervalSeconds = 86400): void
{
    $status = loadEnergyOffersImportStatus();
    $lastRun = (int) ($status['last_run'] ?? 0);
    $lastStarted = (int) ($status['last_started'] ?? 0);
    $lastActivity = max($lastRun, $lastStarted);
    if ($lastActivity > 0 && (time() - $lastActivity) < $intervalSeconds) {
        return;
    }
    runEnergyOffersImport(true);
}

function parseItalianNumber(?string $value): ?float
{
    if ($value === null) {
        return null;
    }
    $normalized = str_replace(['.', ' '], '', $value);
    $normalized = str_replace(',', '.', $normalized);
    $normalized = trim($normalized);
    if ($normalized === '' || !is_numeric($normalized)) {
        return null;
    }
    return (float) $normalized;
}

function extractMaxByRegex(string $text, string $pattern, int $group = 1): ?float
{
    if (!preg_match_all($pattern, $text, $matches)) {
        return null;
    }
    $values = [];
    foreach ($matches[$group] ?? [] as $raw) {
        $num = parseItalianNumber((string) $raw);
        if ($num !== null) {
            $values[] = $num;
        }
    }
    if ($values === []) {
        return null;
    }
    return max($values);
}

function extractFirstByRegex(string $text, string $pattern, int $group = 1): ?float
{
    if (!preg_match($pattern, $text, $matches)) {
        return null;
    }
    $raw = $matches[$group] ?? null;
    if ($raw === null) {
        return null;
    }
    return parseItalianNumber((string) $raw);
}

/**
 * @return array<string, mixed>|null
 */
function resolveTenantLicense(PDO $pdo, int $tenantId): ?array
{
    if ($tenantId <= 0) {
        return null;
    }

    $stmt = $pdo->prepare(
        'SELECT l.id, l.code, l.label, l.max_users, l.term_months, l.is_active, l.expires_at,
                tl.max_users_override, tl.assigned_at, tl.revoked_at
         FROM tenant_licenses tl
         INNER JOIN licenses l ON l.id = tl.license_id
         WHERE tl.tenant_id = :tenant AND tl.revoked_at IS NULL
         ORDER BY tl.assigned_at DESC
         LIMIT 1'
    );
    $stmt->execute([':tenant' => $tenantId]);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($row === false) {
        return null;
    }

    if ((int) ($row['is_active'] ?? 0) !== 1) {
        return null;
    }

    return $row;
}

function resolveLicensePlanKey(?array $license): string
{
    if ($license === null) {
        return 'start';
    }

    $label = strtolower(trim((string) ($license['label'] ?? '')));
    $code = strtolower(trim((string) ($license['code'] ?? '')));
    $haystack = trim($label . ' ' . $code);

    if ($haystack !== '') {
        if (str_contains($haystack, 'start plus') || str_contains($haystack, 'start+')) {
            return 'start_plus';
        }
        if (str_contains($haystack, 'start')) {
            return 'start';
        }
        if (str_contains($haystack, 'core')) {
            return 'core';
        }
        if (str_contains($haystack, 'business')) {
            return 'business';
        }
    }

    return 'start';
}

function isLicenseExpired(?array $license): bool
{
    if ($license === null) {
        return false;
    }

    $expiresAt = $license['expires_at'] ?? null;
    if ($expiresAt === null || trim((string) $expiresAt) === '') {
        return false;
    }

    $timestamp = strtotime((string) $expiresAt);
    if ($timestamp === false) {
        return false;
    }

    return $timestamp < strtotime('today');
}

/**
 * @return array<string, array<int, string>>
 */
function buildPlanModuleMap(): array
{
    $startModules = [
        'dashboard',
        'sim_stock',
        'products',
        'products_list',
        'customers',
        'offers',
        'sales_create',
        'sales_list',
        'guide',
        'settings',
    ];

    $startPlusModules = array_merge($startModules, [
        'reports',
        'support_requests',
        'product_requests',
    ]);

    $coreModules = array_merge($startPlusModules, [
        'energy_contracts',
    ]);

    return [
        'start' => $startModules,
        'start_plus' => $startPlusModules,
        'core' => $coreModules,
        'business' => $coreModules,
    ];
}

function resolveOperatorLimitForLicense(?array $license): int
{
    if (isLicenseExpired($license)) {
        return 0;
    }

    $planKey = resolveLicensePlanKey($license);
    return match ($planKey) {
        'start_plus' => 2,
        'core' => 2,
        'business' => 4,
        default => 1,
    };
}

/**
 * @return array<string, mixed>|null
 */
function resolveLicenseById(PDO $pdo, int $licenseId): ?array
{
    if ($licenseId <= 0) {
        return null;
    }

    $stmt = $pdo->prepare(
        'SELECT id, code, label, max_users, term_months, is_active, expires_at
         FROM licenses
         WHERE id = :id
         LIMIT 1'
    );
    $stmt->execute([':id' => $licenseId]);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);

    return $row !== false ? $row : null;
}

/**
 * @return array{plan_key:string,modules:array<int,string>,license:array<string,mixed>|null}
 */
function resolveTenantModuleAccess(PDO $pdo, ?array $currentUser, \App\Services\AuthService $authService): array
{
    $planModules = buildPlanModuleMap();
    $allModules = [];
    foreach ($planModules as $modules) {
        $allModules = array_merge($allModules, $modules);
    }
    $allModules = array_values(array_unique($allModules));

    if ($currentUser === null) {
        return [
            'plan_key' => 'guest',
            'modules' => $allModules,
            'license' => null,
        ];
    }

    if ($authService->hasRole('admin')) {
        return [
            'plan_key' => 'admin',
            'modules' => $allModules,
            'license' => null,
        ];
    }

    $tenantId = (int) ($currentUser['tenant_id'] ?? 1);
    $license = resolveTenantLicense($pdo, $tenantId);
    if (isLicenseExpired($license)) {
        return [
            'plan_key' => 'expired',
            'modules' => [],
            'license' => $license,
        ];
    }

    $planKey = resolveLicensePlanKey($license);
    $modules = $planModules[$planKey] ?? $planModules['start'];

    return [
        'plan_key' => $planKey,
        'modules' => $modules,
        'license' => $license,
    ];
}

/**
 * @return array<string, string>
 */
function buildPageModuleMap(): array
{
    return [
        'dashboard' => 'dashboard',
        'sim_stock' => 'sim_stock',
        'products' => 'products',
        'products_list' => 'products_list',
        'customers' => 'customers',
        'offers' => 'offers',
        'sales_create' => 'sales_create',
        'sales_list' => 'sales_list',
        'energy_contracts' => 'energy_contracts',
        'product_requests' => 'product_requests',
        'product_request' => 'product_requests',
        'support_requests' => 'support_requests',
        'support_request' => 'support_requests',
        'reports' => 'reports',
        'guide' => 'guide',
        'settings' => 'settings',
    ];
}

function extractPeriodConsumption(string $text, string $unit): ?float
{
    $unitPattern = $unit === 'kwh'
        ? '(?:kwh)'
        : '(?:smc|sm3|sm³)';

    $patterns = [
        '/consumo\s+del\s+periodo\s*([0-9][0-9\.,]*)\s*' . $unitPattern . '/i',
        '/consumi\s+rilevati\s+nel\s+periodo[^0-9]*([0-9][0-9\.,]*)\s*' . $unitPattern . '/i',
        '/consumo\s+totale\s+fatturato\s+del\s+periodo\s*([0-9][0-9\.,]*)\s*' . $unitPattern . '/i',
        '/quota\s+consumi\s*([0-9][0-9\.,]*)\s*' . $unitPattern . '/i',
    ];

    foreach ($patterns as $pattern) {
        $value = extractFirstByRegex($text, $pattern);
        if ($value !== null && $value > 0) {
            return $value;
        }
    }

    return null;
}

function extractBillAmount(string $text): ?float
{
    $patterns = [
        '/totale\s+da\s+pagare\s*([0-9\.,]+)\s*€/i',
        '/totale\s+bolletta\s*([0-9\.,]+)\s*€/i',
        '/quanto\s+pago\s+per\s+questa\s+bolletta\?\s*([0-9\.,]+)\s*€/i',
        '/importo\s+totale\s*[:\-]?\s*€?\s*([0-9\.,]+)/i',
    ];
    foreach ($patterns as $pattern) {
        $value = extractFirstByRegex($text, $pattern);
        if ($value !== null && $value > 0) {
            return $value;
        }
    }
    $value = extractMaxByRegex($text, '/€\s*([0-9\.,]+)/i');
    if ($value !== null && $value > 0) {
        return $value;
    }
    return null;
}

function parseEnergyBillPdf(string $path): array
{
    $parser = new \Smalot\PdfParser\Parser();
    $pdf = $parser->parseFile($path);
    $text = $pdf->getText();
    $text = preg_replace('/\s+/', ' ', (string) $text) ?? '';

    $luceKwh = extractPeriodConsumption($text, 'kwh') ?? extractMaxByRegex($text, '/([0-9][0-9\.,]*)\s*kwh/i');
    $gasSmc = extractPeriodConsumption($text, 'smc') ?? extractMaxByRegex($text, '/([0-9][0-9\.,]*)\s*(?:smc|sm3|sm³)/i');
    $billAmount = extractBillAmount($text);

    $frequency = null;
    if (preg_match('/bimestre|bimestrale|due\s+mesi/i', $text)) {
        $frequency = 'bimonthly';
    } elseif (preg_match('/periodo\s+[a-z]{3}\.?\s+\d{4}\s*-\s*[a-z]{3}\.?\s+\d{4}/i', $text)) {
        $frequency = 'bimonthly';
    } elseif (preg_match('/mensile|mese/i', $text)) {
        $frequency = 'monthly';
    }

    return [
        'luce_kwh' => $luceKwh,
        'gas_smc' => $gasSmc,
        'bill_amount' => $billAmount,
        'bill_frequency' => $frequency,
    ];
}

function sendGuideSupportEmail(
    string $recipient,
    string $subject,
    string $message,
    ?string $resendApiKey,
    ?string $resendFrom,
    ?string $resendFromName,
    ?string $htmlMessage = null
): bool {
    if ($resendApiKey !== null && $resendApiKey !== '' && function_exists('curl_init')) {
        $fromEmail = $resendFrom !== null && $resendFrom !== '' ? $resendFrom : 'support@coresuite.test';
        $fromLabel = $resendFromName !== null && $resendFromName !== '' ? $resendFromName : null;
        $from = $fromLabel !== null ? $fromLabel . ' <' . $fromEmail . '>' : $fromEmail;

        $payload = json_encode([
            'from' => $from,
            'to' => [$recipient],
            'subject' => $subject,
            'text' => $message,
            'html' => $htmlMessage,
        ]);

        if ($payload === false) {
            return false;
        }

        $ch = curl_init('https://api.resend.com/emails');
        if ($ch === false) {
            return false;
        }

        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => $payload,
            CURLOPT_HTTPHEADER => [
                'Authorization: Bearer ' . $resendApiKey,
                'Content-Type: application/json',
            ],
            CURLOPT_TIMEOUT => 10,
        ]);
        $response = curl_exec($ch);
        $status = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        return $response !== false && $status >= 200 && $status < 300;
    }

    $headers = [];
    if ($resendFrom !== null && $resendFrom !== '') {
        $from = $resendFromName !== null && $resendFromName !== ''
            ? $resendFromName . ' <' . $resendFrom . '>'
            : $resendFrom;
        $headers[] = 'From: ' . $from;
    }

    if ($htmlMessage !== null && $htmlMessage !== '') {
        $headers[] = 'MIME-Version: 1.0';
        $headers[] = 'Content-Type: text/html; charset=UTF-8';
        return @mail($recipient, $subject, $htmlMessage, implode("\r\n", $headers));
    }

    return @mail($recipient, $subject, $message, implode("\r\n", $headers));
}

require __DIR__ . '/../config/database.php';

spl_autoload_register(static function (string $class): void {
    $prefix = 'App\\';
    $baseDir = __DIR__ . '/../app/';
    if (str_starts_with($class, $prefix)) {
        $relative = str_replace('\\', '/', substr($class, strlen($prefix)));
        $path = $baseDir . $relative . '.php';
        if (file_exists($path)) {
            require $path;
        }
    }
});

use App\Controllers\AuthController;
use App\Controllers\ICCIDController;
use App\Controllers\CustomerController;
use App\Controllers\OffersController;
use App\Controllers\ProductController;
use App\Controllers\ProductRequestController;
use App\Controllers\ReportsController;
use App\Controllers\SalesController;
use App\Controllers\SupportRequestController;
use App\Controllers\EnergyContractController;
use App\Controllers\SsoController;
use App\Controllers\PdaImportController;
use App\Services\AuthService;
use App\Services\CustomerService;
use App\Services\DiscountCampaignService;
use App\Services\ICCIDService;
use App\Services\OffersService;
use App\Services\ProductService;
use App\Services\ProductRequestService;
use App\Services\ReportsService;
use App\Services\SaleNotificationService;
use App\Services\SalesService;
use App\Services\StockMonitorService;
use App\Services\SupportRequestService;
use App\Services\UserService;
use App\Services\PdaSettingsService;
use App\Services\ProviderService;
use App\Services\NotificationDispatcher;
use App\Services\EnergyProviderService;
use App\Services\EnergyContractService;
use App\Services\EnergyOfferService;
use App\Services\SystemNotificationService;
use App\Services\PrivacyPolicyService;
use App\Services\SsoService;
use App\Services\PdaImportService;
use App\Services\ReceiptSettingsService;
use App\Services\LicenseService;
use App\Services\TenantService;
use App\Services\TenantContext;

$pdo = Database::getConnection();

$alertsConfig = $GLOBALS['config']['alerts'] ?? [];
$alertEmail = $alertsConfig['email'] ?? null;
$resendApiKey = $alertsConfig['resend_api_key'] ?? null;
$resendFrom = $alertsConfig['resend_from'] ?? null;
$resendFromName = $alertsConfig['resend_from_name'] ?? null;
$saleFulfilmentEmail = $alertsConfig['sales_fulfilment_email'] ?? null;
$appConfig = $GLOBALS['config']['app'] ?? [];
$appName = is_array($appConfig) && isset($appConfig['name']) && is_string($appConfig['name']) && $appConfig['name'] !== ''
    ? (string) $appConfig['name']
    : 'Gestionale Telefonia';
$configuredPortalUrl = is_array($appConfig) && isset($appConfig['portal_url']) && is_string($appConfig['portal_url']) && $appConfig['portal_url'] !== ''
    ? trim($appConfig['portal_url'])
    : null;
$portalLoginUrl = $configuredPortalUrl !== null && $configuredPortalUrl !== '' ? $configuredPortalUrl : null;
if ($portalLoginUrl === null && !empty($_SERVER['HTTP_HOST'])) {
    $httpsValue = $_SERVER['HTTPS'] ?? null;
    $scheme = (is_string($httpsValue) && strtolower((string) $httpsValue) !== 'off' && $httpsValue !== '') ? 'https' : 'http';
    $portalLoginUrl = $scheme . '://' . $_SERVER['HTTP_HOST'] . '/public/portal/';
}
$logPath = __DIR__ . '/../storage/logs/stock_alerts.log';
$saleNotificationLog = __DIR__ . '/../storage/logs/sale_notifications.log';
$notificationsConfig = $GLOBALS['config']['notifications'] ?? [];
$notificationsLog = __DIR__ . '/../storage/logs/notifications.log';
$notificationDispatcher = new NotificationDispatcher(
    $notificationsConfig['webhook_url'] ?? null,
    is_array($notificationsConfig['webhook_headers'] ?? null) ? $notificationsConfig['webhook_headers'] : [],
    is_array($notificationsConfig['queue'] ?? null) ? $notificationsConfig['queue'] : null,
    $notificationsLog
);
$systemNotificationService = new SystemNotificationService($pdo, $notificationDispatcher, $notificationsLog);
$GLOBALS['systemNotificationService'] = $systemNotificationService;

if ($saleFulfilmentEmail === null || !filter_var($saleFulfilmentEmail, FILTER_VALIDATE_EMAIL)) {
    $saleFulfilmentEmail = $alertEmail;
}

$authService = new AuthService($pdo);
$iccidService = new ICCIDService($pdo);
$providerService = new ProviderService($pdo);
$customerService = new CustomerService($pdo, $resendApiKey, $resendFrom, $appName, $portalLoginUrl, $resendFromName);
$offersService = new OffersService($pdo);
$reportsService = new ReportsService($pdo);
$productService = new ProductService($pdo);
$productRequestService = new ProductRequestService($pdo);
$salesService = new SalesService($pdo);
$discountCampaignService = new DiscountCampaignService($pdo);
$privacyPolicyService = new PrivacyPolicyService($pdo);
$pdaImportService = new PdaImportService($pdo, $customerService);
$pdaSettingsService = new PdaSettingsService();
$receiptSettingsService = new ReceiptSettingsService();
$energyProviderService = new EnergyProviderService($pdo);
$energyContractService = new EnergyContractService($pdo);
$energyOfferService = new EnergyOfferService($pdo);
$licenseService = new LicenseService($pdo);
$tenantService = new TenantService($pdo, $resendApiKey, $resendFrom, $resendFromName, $appName);
$supportRequestService = new SupportRequestService($pdo);
$userService = new UserService($pdo, $resendApiKey, $resendFrom, $resendFromName, $appName);
$stockMonitorService = new StockMonitorService($pdo, $alertEmail, $logPath, $resendApiKey, $resendFrom, $systemNotificationService);
$saleNotificationService = new SaleNotificationService(
    $resendApiKey,
    $resendFrom,
    $resendFromName,
    $saleFulfilmentEmail,
    $appName,
    $saleNotificationLog,
    $systemNotificationService
);
$ssoConfig = $GLOBALS['config']['sso'] ?? [];
$ssoService = new SsoService($pdo, is_array($ssoConfig) ? $ssoConfig : []);

$authController = new AuthController($authService);
$iccidController = new ICCIDController($iccidService);
$customerController = new CustomerController($customerService);
$offersController = new OffersController($offersService);
$reportsController = new ReportsController($reportsService);
$productController = new ProductController($productService);
$productRequestController = new ProductRequestController($productRequestService);
$salesController = new SalesController($salesService, $discountCampaignService, $saleNotificationService);
$supportRequestController = new SupportRequestController($supportRequestService);
$ssoController = new SsoController($ssoService);
$pdaImportController = new PdaImportController($pdaImportService);
$energyContractController = new EnergyContractController($energyContractService);

$page = $_GET['page'] ?? 'landing';
$method = $_SERVER['REQUEST_METHOD'] ?? 'GET';
$currentUser = $authService->currentUser();
TenantContext::setTenantId(isset($currentUser['tenant_id']) ? (int) $currentUser['tenant_id'] : 1);

$moduleAccess = resolveTenantModuleAccess($pdo, $currentUser, $authService);
$enabledModules = $moduleAccess['modules'];
$GLOBALS['enabledModules'] = $enabledModules;
$GLOBALS['tenantPlanKey'] = $moduleAccess['plan_key'];
$GLOBALS['tenantLicense'] = $moduleAccess['license'];

if ($currentUser !== null && $authService->hasRole('admin')) {
    maybeScheduleEnergyOffersImport();
}

if ($page === 'sso_token') {
    if ($method !== 'POST') {
        http_response_code(405);
        header('Allow: POST');
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode(['error' => 'method_not_allowed']);
        exit;
    }

    $input = $_POST;
    if ($input === [] && isset($_SERVER['CONTENT_TYPE']) && str_contains((string) $_SERVER['CONTENT_TYPE'], 'application/json')) {
        $rawBody = file_get_contents('php://input');
        $decoded = json_decode($rawBody ?: '{}', true);
        if (is_array($decoded)) {
            $input = $decoded;
        }
    }

    $response = $ssoController->token($input);
    http_response_code($response['status']);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode($response['body']);
    exit;
}

if ($page === 'sso_authorize') {
    if (!$ssoService->isEnabled()) {
        http_response_code(503);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode(['error' => 'sso_disabled']);
        exit;
    }

    if ($currentUser === null) {
        $query = $_GET;
        $query['page'] = 'sso_authorize';
        $queryString = http_build_query($query);
        $_SESSION['login_redirect'] = 'index.php' . ($queryString !== '' ? '?' . $queryString : '');
        header('Location: index.php?page=login');
        exit;
    }

    $result = $ssoController->authorize($_GET, $currentUser);
    if (!($result['success'] ?? false)) {
        http_response_code($result['status'] ?? 400);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode([
            'error' => $result['error'] ?? 'Operazione non riuscita.',
        ]);
        exit;
    }

    header('Location: ' . $result['redirect']);
    exit;
}

if ($page === 'providers_api') {
    if ($currentUser === null) {
        http_response_code(401);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode(['error' => 'not_authenticated']);
        exit;
    }

    if (!$authService->hasRole('admin')) {
        http_response_code(403);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode(['error' => 'forbidden']);
        exit;
    }

    $input = $_POST;
    if ($input === []) {
        $rawBody = file_get_contents('php://input') ?: '';
        $contentType = (string) ($_SERVER['CONTENT_TYPE'] ?? '');
        if ($rawBody !== '' && str_contains($contentType, 'application/json')) {
            $decoded = json_decode($rawBody, true);
            if (is_array($decoded)) {
                $input = $decoded;
            }
        } elseif ($rawBody !== '') {
            parse_str($rawBody, $parsed);
            if (is_array($parsed)) {
                $input = $parsed;
            }
        }
    }

    $userId = (int) ($currentUser['id'] ?? 0);
    $response = ['status' => 200, 'body' => ['success' => true]];

    switch ($method) {
        case 'GET':
            $response['body'] = [
                'success' => true,
                'providers' => $providerService->listProviders(),
            ];
            break;
        case 'POST':
            $response['body'] = $providerService->createProvider($input, $userId);
            $response['status'] = ($response['body']['success'] ?? false) ? 201 : 400;
            break;
        case 'PUT':
            $providerId = isset($input['id']) ? (int) $input['id'] : (int) ($_GET['id'] ?? 0);
            $response['body'] = $providerService->updateProvider($providerId, $input, $userId);
            $response['status'] = ($response['body']['success'] ?? false) ? 200 : 400;
            break;
        case 'DELETE':
            $providerId = isset($input['id']) ? (int) $input['id'] : (int) ($_GET['id'] ?? 0);
            $response['body'] = $providerService->deleteProvider($providerId, $userId);
            $response['status'] = ($response['body']['success'] ?? false) ? 200 : 400;
            break;
        default:
            http_response_code(405);
            header('Allow: GET, POST, PUT, DELETE');
            header('Content-Type: application/json; charset=utf-8');
            echo json_encode(['error' => 'method_not_allowed']);
            exit;
    }

    http_response_code($response['status']);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode($response['body']);
    exit;
}

if ($page === 'logout') {
    $authController->logout();
}

if ($page === 'login' && $method === 'POST') {
    $result = $authController->login($_POST);
    if ($result['success']) {
        $pending = $_SESSION['login_redirect'] ?? null;
        unset($_SESSION['login_redirect']);
        $target = is_string($pending) && $pending !== ''
            ? sanitizeInternalUrl($pending, 'index.php')
            : 'index.php';
        header('Location: ' . $target);
        exit;
    }

    if (!empty($result['mfa_required']) && isset($result['redirect'])) {
        header('Location: ' . $result['redirect']);
        exit;
    }

    render('login', [
        'errors' => $result['errors'] ?? [],
        'appName' => $GLOBALS['config']['app']['name'] ?? 'Gestionale Telefonia',
        'oldInput' => $result['old'] ?? ['username' => '', 'remember_me' => false],
    ], false);
    exit;
}

if ($page === 'global_search') {
    if ($method !== 'GET') {
        jsonResponse(['error' => 'method_not_allowed'], 405);
    }

    if ($currentUser === null) {
        jsonResponse(['error' => 'not_authenticated'], 401);
    }

    $term = trim((string) ($_GET['q'] ?? ''));
    $minLength = 2;
    $termLength = function_exists('mb_strlen') ? mb_strlen($term, 'UTF-8') : strlen($term);
    if ($term === '' || $termLength < $minLength) {
        jsonResponse([
            'success' => true,
            'query' => $term,
            'sections' => [],
        ]);
    }

    $limit = 5;
    $sections = [];

    $normalize = static function (string $value): string {
        $trimmed = trim($value);
        if (function_exists('mb_strtolower')) {
            return mb_strtolower($trimmed, 'UTF-8');
        }
        return strtolower($trimmed);
    };

    $needle = $normalize($term);
    $matches = static function (string $haystack) use ($needle, $normalize): bool {
        if ($needle === '') {
            return false;
        }
        return str_contains($normalize($haystack), $needle);
    };

    $addSection = static function (string $title, array $items) use (&$sections): void {
        if ($items !== []) {
            $sections[] = [
                'title' => $title,
                'items' => $items,
            ];
        }
    };

    $navItems = [
        ['label' => 'Dashboard', 'keywords' => 'home panoramica', 'url' => 'index.php?page=dashboard', 'module' => 'dashboard'],
        ['label' => 'Magazzino SIM', 'keywords' => 'sim stock iccid', 'url' => 'index.php?page=sim_stock', 'module' => 'sim_stock'],
        ['label' => 'Prodotti', 'keywords' => 'catalogo', 'url' => 'index.php?page=products', 'module' => 'products'],
        ['label' => 'Lista prodotti', 'keywords' => 'inventario', 'url' => 'index.php?page=products_list', 'module' => 'products_list'],
        ['label' => 'Clienti', 'keywords' => 'anagrafiche', 'url' => 'index.php?page=customers', 'module' => 'customers'],
        ['label' => 'Listini', 'keywords' => 'offerte', 'url' => 'index.php?page=offers', 'module' => 'offers'],
        ['label' => 'Nuova vendita', 'keywords' => 'cassa', 'url' => 'index.php?page=sales_create', 'module' => 'sales_create'],
        ['label' => 'Storico vendite', 'keywords' => 'transazioni', 'url' => 'index.php?page=sales_list', 'module' => 'sales_list'],
        ['label' => 'Contratti energia', 'keywords' => 'energia', 'url' => 'index.php?page=energy_contracts', 'module' => 'energy_contracts'],
        ['label' => 'Ordini store', 'keywords' => 'richieste prodotti', 'url' => 'index.php?page=product_requests', 'module' => 'product_requests'],
        ['label' => 'Supporto clienti', 'keywords' => 'assistenza', 'url' => 'index.php?page=support_requests', 'module' => 'support_requests'],
        ['label' => 'Report', 'keywords' => 'statistiche', 'url' => 'index.php?page=reports', 'module' => 'reports'],
        ['label' => 'Notifiche', 'keywords' => 'alert', 'url' => 'index.php?page=notifications'],
        ['label' => 'Guida', 'keywords' => 'help', 'url' => 'index.php?page=guide', 'module' => 'guide'],
        ['label' => 'Impostazioni', 'keywords' => 'configurazione', 'url' => 'index.php?page=settings', 'module' => 'settings'],
        ['label' => 'Sicurezza account', 'keywords' => 'mfa', 'url' => 'index.php?page=security'],
        ['label' => 'Profilo', 'keywords' => 'utente', 'url' => 'index.php?page=profile'],
    ];

    if ($authService->hasRole('admin')) {
        $navItems[] = ['label' => 'Debug PDA', 'keywords' => 'import pda', 'url' => 'index.php?page=pda_imports'];
        $navItems[] = ['label' => 'Licenze & Tenant', 'keywords' => 'licenze tenant multi-tenant', 'url' => 'index.php?page=licenses'];
    }

    $navMatches = [];
    foreach ($navItems as $item) {
        $module = $item['module'] ?? null;
        if ($module !== null && !in_array($module, $enabledModules, true)) {
            continue;
        }
        $haystack = $item['label'] . ' ' . ($item['keywords'] ?? '');
        if ($matches($haystack)) {
            $navMatches[] = [
                'title' => $item['label'],
                'subtitle' => $item['keywords'] ?? '',
                'url' => $item['url'],
                'meta' => 'Sezione',
            ];
        }
    }
    $addSection('Navigazione', $navMatches);

    $customerResults = $customerController->listPaginated(1, $limit, $term);
    $customerItems = [];
    foreach ($customerResults['rows'] ?? [] as $row) {
        $customerId = (int) ($row['id'] ?? 0);
        if ($customerId <= 0) {
            continue;
        }
        $title = trim((string) ($row['fullname'] ?? ''));
        if ($title === '') {
            $title = 'Cliente #' . $customerId;
        }
        $subtitleParts = array_filter([
            isset($row['email']) && $row['email'] !== '' ? (string) $row['email'] : null,
            isset($row['phone']) && $row['phone'] !== '' ? (string) $row['phone'] : null,
            isset($row['tax_code']) && $row['tax_code'] !== '' ? (string) $row['tax_code'] : null,
        ]);
        $customerItems[] = [
            'title' => $title,
            'subtitle' => implode(' • ', $subtitleParts),
            'url' => 'index.php?page=customers&edit=' . $customerId,
            'meta' => 'Cliente',
        ];
    }
    $addSection('Clienti', $customerItems);

    $productResults = $productController->listPaginated(1, $limit, $term);
    $productItems = [];
    foreach ($productResults['rows'] ?? [] as $row) {
        $productId = (int) ($row['id'] ?? 0);
        if ($productId <= 0) {
            continue;
        }
        $title = trim((string) ($row['name'] ?? ''));
        if ($title === '') {
            $title = 'Prodotto #' . $productId;
        }
        $subtitleParts = [];
        if (!empty($row['sku'])) {
            $subtitleParts[] = 'SKU: ' . $row['sku'];
        }
        if (!empty($row['imei'])) {
            $subtitleParts[] = 'IMEI: ' . $row['imei'];
        }
        if (!empty($row['category'])) {
            $subtitleParts[] = (string) $row['category'];
        }
        if (array_key_exists('price', $row)) {
            $subtitleParts[] = '€ ' . number_format((float) $row['price'], 2, ',', '.');
        }
        $productItems[] = [
            'title' => $title,
            'subtitle' => implode(' • ', array_filter($subtitleParts)),
            'url' => 'index.php?page=products&edit=' . $productId,
            'meta' => 'Prodotto',
        ];
    }
    $addSection('Prodotti', $productItems);

    $offersResults = $offersController->listPaginated(1, $limit, null, $term);
    $offerItems = [];
    foreach ($offersResults['rows'] ?? [] as $row) {
        $offerId = (int) ($row['id'] ?? 0);
        if ($offerId <= 0) {
            continue;
        }
        $title = trim((string) ($row['title'] ?? ''));
        if ($title === '') {
            $title = 'Offerta #' . $offerId;
        }
        $subtitleParts = [];
        if (!empty($row['provider_name'])) {
            $subtitleParts[] = (string) $row['provider_name'];
        }
        if (array_key_exists('price', $row)) {
            $subtitleParts[] = '€ ' . number_format((float) $row['price'], 2, ',', '.');
        }
        $offerItems[] = [
            'title' => $title,
            'subtitle' => implode(' • ', array_filter($subtitleParts)),
            'url' => 'index.php?page=offers&edit=' . $offerId,
            'meta' => 'Offerta',
        ];
    }
    $addSection('Listini', $offerItems);

    $iccidResults = $iccidController->listPaginated(1, $limit, null, $term);
    $iccidItems = [];
    foreach ($iccidResults['rows'] ?? [] as $row) {
        $iccid = trim((string) ($row['iccid'] ?? ''));
        if ($iccid === '') {
            continue;
        }
        $subtitleParts = [];
        if (!empty($row['provider_name'])) {
            $subtitleParts[] = (string) $row['provider_name'];
        }
        if (!empty($row['status'])) {
            $subtitleParts[] = (string) $row['status'];
        }
        $iccidItems[] = [
            'title' => 'ICCID ' . $iccid,
            'subtitle' => implode(' • ', array_filter($subtitleParts)),
            'url' => 'index.php?page=sim_stock&search=' . rawurlencode($iccid),
            'meta' => 'SIM',
        ];
    }
    $addSection('Magazzino SIM', $iccidItems);

    $salesResults = $salesController->listSales(['q' => $term], 1, $limit);
    $saleItems = [];
    foreach ($salesResults['rows'] ?? [] as $row) {
        $saleId = (int) ($row['id'] ?? 0);
        if ($saleId <= 0) {
            continue;
        }
        $customer = trim((string) ($row['customer_name'] ?? $row['customer_fullname'] ?? ''));
        $subtitleParts = [];
        if ($customer !== '') {
            $subtitleParts[] = 'Cliente: ' . $customer;
        }
        if (array_key_exists('total', $row)) {
            $subtitleParts[] = 'Totale € ' . number_format((float) $row['total'], 2, ',', '.');
        }
        if (!empty($row['status'])) {
            $subtitleParts[] = 'Stato: ' . $row['status'];
        }
        $saleItems[] = [
            'title' => 'Vendita #' . $saleId,
            'subtitle' => implode(' • ', array_filter($subtitleParts)),
            'url' => 'index.php?page=sales_list&q=' . rawurlencode((string) $saleId),
            'meta' => 'Vendita',
        ];
    }
    $addSection('Vendite', $saleItems);

    $energyContracts = $energyContractController->search($term, $limit);
    $energyItems = [];
    foreach ($energyContracts as $row) {
        $contractId = (int) ($row['id'] ?? 0);
        if ($contractId <= 0) {
            continue;
        }
        $createdAtRaw = isset($row['created_at']) ? (string) $row['created_at'] : '';
        $createdAtDate = '';
        if ($createdAtRaw !== '') {
            $timestamp = strtotime($createdAtRaw);
            if ($timestamp !== false) {
                $createdAtDate = date('Y-m-d', $timestamp);
            }
        }
        $customerName = trim((string) ($row['customer_name'] ?? ''));
        $title = $customerName !== '' ? $customerName : 'Contratto #' . $contractId;
        $subtitleParts = [];
        if (!empty($row['provider_name'])) {
            $subtitleParts[] = (string) $row['provider_name'];
        }
        if (!empty($row['contract_type'])) {
            $subtitleParts[] = strtoupper((string) $row['contract_type']);
        }
        if (array_key_exists('token_value', $row)) {
            $subtitleParts[] = 'Provvigione € ' . number_format((float) $row['token_value'], 2, ',', '.');
        }
        $url = 'index.php?page=energy_contracts&focus=' . $contractId;
        if ($createdAtDate !== '') {
            $url .= '&period=month&date=' . rawurlencode($createdAtDate);
        }
        $energyItems[] = [
            'title' => $title,
            'subtitle' => implode(' • ', array_filter($subtitleParts)),
            'url' => $url,
            'meta' => 'Contratto energia',
        ];
    }
    $addSection('Contratti energia', $energyItems);

    $supportResults = $supportRequestController->list(['q' => $term], 1, $limit);
    $supportItems = [];
    foreach ($supportResults['rows'] ?? [] as $row) {
        $requestId = (int) ($row['id'] ?? 0);
        if ($requestId <= 0) {
            continue;
        }
        $title = trim((string) ($row['subject'] ?? ''));
        if ($title === '') {
            $title = 'Richiesta #' . $requestId;
        }
        $subtitleParts = [];
        if (!empty($row['customer_name'])) {
            $subtitleParts[] = 'Cliente: ' . $row['customer_name'];
        }
        if (!empty($row['status'])) {
            $subtitleParts[] = 'Stato: ' . $row['status'];
        }
        $supportItems[] = [
            'title' => $title,
            'subtitle' => implode(' • ', array_filter($subtitleParts)),
            'url' => 'index.php?page=support_request&request_id=' . $requestId,
            'meta' => 'Supporto',
        ];
    }
    $addSection('Supporto clienti', $supportItems);

    $productRequestResults = $productRequestController->list(['q' => $term], 1, $limit);
    $productRequestItems = [];
    foreach ($productRequestResults['rows'] ?? [] as $row) {
        $requestId = (int) ($row['id'] ?? 0);
        if ($requestId <= 0) {
            continue;
        }
        $title = trim((string) ($row['product_name'] ?? ''));
        if ($title === '') {
            $title = 'Richiesta #' . $requestId;
        }
        $subtitleParts = [];
        if (!empty($row['customer_name'])) {
            $subtitleParts[] = 'Cliente: ' . $row['customer_name'];
        }
        if (!empty($row['status'])) {
            $subtitleParts[] = 'Stato: ' . $row['status'];
        }
        $productRequestItems[] = [
            'title' => $title,
            'subtitle' => implode(' • ', array_filter($subtitleParts)),
            'url' => 'index.php?page=product_request&request_id=' . $requestId,
            'meta' => 'Ordine store',
        ];
    }
    $addSection('Ordini store', $productRequestItems);

    jsonResponse([
        'success' => true,
        'query' => $term,
        'sections' => $sections,
    ]);
}

if ($currentUser === null && !in_array($page, ['landing', 'login', 'login_mfa', 'sso_authorize', 'sso_token'], true)) {
    header('Location: index.php?page=login');
    exit;
}

if ($currentUser !== null && !$authService->hasRole('admin')) {
    if (($moduleAccess['plan_key'] ?? '') === 'expired') {
        pushFlashToast([
            'type' => 'warning',
            'title' => 'Licenza scaduta',
            'message' => 'La licenza è scaduta. Contatta l’amministratore per il rinnovo.',
            'duration' => 0,
            'dismissible' => false,
        ]);
        $authController->logout();
        header('Location: index.php?page=login');
        exit;
    }
}

if ($currentUser !== null) {
    $allowedWithoutMfa = ['security', 'logout', 'notifications_stream', 'notifications_mark_all_read'];
    $state = $authController->getSecurityState((int) $currentUser['id']);
    $hasMfa = is_array($state) && (($state['mfa_enabled'] ?? false) === true);

    if ($hasMfa) {
        unset($_SESSION['mfa_enforcement_prompted']);
    } elseif (!in_array($page, $allowedWithoutMfa, true)) {
        if (empty($_SESSION['mfa_enforcement_prompted'])) {
            pushFlashToast([
                'type' => 'warning',
                'title' => 'Proteggi il tuo account',
                'message' => 'Configura l’autenticazione a due fattori per continuare a usare la piattaforma.',
                'duration' => 0,
                'dismissible' => false,
            ]);
            $_SESSION['mfa_enforcement_prompted'] = true;
        }

        header('Location: index.php?page=security&setup=1');
        exit;
    }

    if ($hasMfa) {
        $allowedWithoutReceipt = ['settings', 'security', 'logout', 'notifications_stream', 'notifications_mark_all_read'];
        if ($receiptSettingsService->isConfigured()) {
            unset($_SESSION['receipt_enforcement_prompted']);
        } elseif (!in_array($page, $allowedWithoutReceipt, true)) {
            if (empty($_SESSION['receipt_enforcement_prompted'])) {
                pushFlashToast([
                    'type' => 'info',
                    'title' => 'Completa la configurazione iniziale',
                    'message' => 'Imposta le diciture dello scontrino per completare l’avvio del gestionale.',
                    'duration' => 0,
                    'dismissible' => false,
                ]);
                $_SESSION['receipt_enforcement_prompted'] = true;
            }

            header('Location: index.php?page=settings&receipt_open=1');
            exit;
        }
    }
}

if ($page === 'login_mfa') {
    if ($currentUser !== null) {
        header('Location: index.php');
        exit;
    }

    if (!$authController->hasPendingMfa()) {
        $authController->cancelPendingMfa();
        header('Location: index.php?page=login');
        exit;
    }

    $errors = [];
    if ($method === 'POST') {
        $action = isset($_POST['action']) ? (string) $_POST['action'] : 'verify';
        if ($action === 'cancel') {
            $authController->cancelPendingMfa();
            header('Location: index.php?page=login');
            exit;
        }

        $verification = $authController->verifyMfa($_POST);
        if ($verification['success'] ?? false) {
            header('Location: index.php');
            exit;
        }
        if (!empty($verification['error'])) {
            $errors[] = (string) $verification['error'];
        }
    }

    $pendingMfa = $authController->getPendingMfa();

    render('login_mfa', [
        'errors' => $errors,
        'pending' => $pendingMfa,
        'appName' => $GLOBALS['config']['app']['name'] ?? 'Gestionale Telefonia',
    ], false);
    exit;
}

if ($page === 'login') {
    if ($currentUser !== null) {
        header('Location: index.php');
        exit;
    }

    render('login', [
        'errors' => [],
        'appName' => $GLOBALS['config']['app']['name'] ?? 'Gestionale Telefonia',
        'oldInput' => ['username' => '', 'remember_me' => false],
    ], false);
    exit;
}

if ($page === 'notifications_mark_all_read') {
    if ($method !== 'POST') {
        http_response_code(405);
        exit;
    }

    $userId = null;
    if (is_array($currentUser) && isset($currentUser['id'])) {
        $userId = (int) $currentUser['id'];
    }

    if (isset($systemNotificationService)) {
        $systemNotificationService->markAllRead($userId);
    }

    $isAjax = isset($_SERVER['HTTP_X_REQUESTED_WITH'])
        && strtolower((string) $_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest';

    if ($isAjax) {
        header('Content-Type: application/json');
        echo json_encode(['success' => true]);
        exit;
    }

    $redirect = isset($_POST['redirect']) ? (string) $_POST['redirect'] : 'index.php';
    $redirect = sanitizeInternalUrl($redirect, 'index.php');
    header('Location: ' . $redirect);
    exit;
}

if ($page === 'notifications_stream') {
    if ($method !== 'GET') {
        http_response_code(405);
        exit;
    }

    if (!isset($systemNotificationService)) {
        http_response_code(503);
        exit;
    }

    header('Content-Type: text/event-stream');
    header('Cache-Control: no-cache, no-store, must-revalidate');
    header('Pragma: no-cache');
    header('Connection: keep-alive');
    if (function_exists('header_remove')) {
        header_remove('Content-Length');
    }
    @ini_set('output_buffering', 'off');
    @ini_set('zlib.output_compression', '0');
    set_time_limit(0);
    ignore_user_abort(true);

    if (session_status() === PHP_SESSION_ACTIVE) {
        session_write_close();
    }

    $userId = null;
    if (is_array($currentUser) && isset($currentUser['id'])) {
        $userId = (int) $currentUser['id'];
    }

    $lastId = isset($_GET['last_id']) ? (int) $_GET['last_id'] : 0;
    $sleepInterval = 5;
    $maxRuntime = 300; // seconds
    $startedAt = time();

    echo "retry: 8000\n\n";
    while (@ob_end_flush()) {
        // drain existing buffers
    }
    flush();

    while (!connection_aborted()) {
        if ((time() - $startedAt) >= $maxRuntime) {
            break;
        }

        $payload = $systemNotificationService->getStreamPayload($userId, $lastId);
        $items = $payload['items'] ?? [];

        if ($items !== []) {
            $lastId = (int) ($payload['last_id'] ?? $lastId);
            $eventData = json_encode([
                'items' => $items,
                'unread_count' => (int) ($payload['unread_count'] ?? 0),
                'last_id' => $lastId,
            ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

            if ($eventData !== false) {
                echo "event: notifications\n";
                echo 'data: ' . $eventData . "\n\n";
                flush();
            }
        }

        $heartbeat = json_encode(['time' => time()], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
        if ($heartbeat !== false) {
            echo "event: heartbeat\n";
            echo 'data: ' . $heartbeat . "\n\n";
            flush();
        }

        sleep($sleepInterval);
    }

    exit;
}

if ($currentUser !== null && !$authService->hasRole('admin')) {
    $pageModuleMap = buildPageModuleMap();
    $requiredModule = $pageModuleMap[$page] ?? null;

    if ($requiredModule !== null && !in_array($requiredModule, $enabledModules, true)) {
        if (isAjaxRequest()) {
            jsonResponse([
                'success' => false,
                'error' => 'license_forbidden',
                'message' => 'Il modulo richiesto non è incluso nel piano attivo.',
            ], 403);
        }

        pushFlashToast([
            'type' => 'warning',
            'title' => 'Modulo non incluso',
            'message' => 'Il modulo richiesto non è incluso nel piano attivo.',
            'duration' => 6000,
            'dismissible' => true,
        ]);
        header('Location: index.php?page=dashboard');
        exit;
    }
}

switch ($page) {
    case 'landing':
        if ($currentUser !== null) {
            header('Location: index.php?page=dashboard');
            exit;
        }
        $landingFeedback = $_SESSION['landing_feedback'] ?? null;
        unset($_SESSION['landing_feedback']);
        $landingOldInput = $_SESSION['landing_old_input'] ?? null;
        unset($_SESSION['landing_old_input']);

        if ($method === 'POST' && (($_POST['action'] ?? '') === 'landing_contact')) {
            $name = trim((string) ($_POST['contact_name'] ?? ''));
            $email = trim((string) ($_POST['contact_email'] ?? ''));
            $company = trim((string) ($_POST['contact_company'] ?? ''));
            $requestType = trim((string) ($_POST['contact_request'] ?? ''));
            $message = trim((string) ($_POST['contact_message'] ?? ''));

            $errors = [];
            if ($email === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
                $errors[] = 'Inserisci un indirizzo email valido.';
            }
            if ($requestType === '') {
                $errors[] = 'Seleziona il tipo di richiesta.';
            }

            if ($errors !== []) {
                $_SESSION['landing_feedback'] = [
                    'success' => false,
                    'message' => 'Controlla i campi del modulo.',
                    'errors' => $errors,
                ];
                $_SESSION['landing_old_input'] = [
                    'contact_name' => $name,
                    'contact_email' => $email,
                    'contact_company' => $company,
                    'contact_request' => $requestType,
                    'contact_message' => $message,
                ];
                header('Location: index.php?page=landing#contatto');
                exit;
            }

            if ($requestType === 'info_piani') {
                $subject = 'Informazioni piani ' . $appName;
                $lines = [];
                $lines[] = 'Ciao' . ($name !== '' ? ' ' . $name : '') . ',';
                $lines[] = '';
                $lines[] = 'Ecco il riepilogo dei piani disponibili:';
                $lines[] = '';
                $lines[] = 'Piano Start (12 mesi, max 1 cassiere) - € 550';
                $lines[] = '- Dashboard, Magazzino SIM, Prodotti, Lista prodotti, Clienti, Listini, Nuova vendita, Storico vendite, Guida completa, Impostazioni.';
                $lines[] = '';
                $lines[] = 'Piano Start Plus (12 mesi, max 1 cassiere) - € 650';
                $lines[] = '- Tutto del Start + Report, Richieste supporto, Ordini store.';
                $lines[] = '';
                $lines[] = 'Piano Core (24 mesi, max 2 cassieri) - € 850';
                $lines[] = '- Tutto del Start Plus + Contratti energia, Report avanzati (KPI), Supporto prioritario.';
                $lines[] = '';
                $lines[] = 'Piano Business (36 mesi, max 4 cassieri) - € 1200';
                $lines[] = '- Tutto del Core + Report personalizzati, SLA dedicato, onboarding/training, integrazioni avanzate.';
                $lines[] = '';
                if ($name !== '') {
                    $lines[] = 'Nome e cognome: ' . $name;
                }
                if ($company !== '') {
                    $lines[] = 'Azienda: ' . $company;
                }
                if ($message !== '') {
                    $lines[] = 'Messaggio: ' . $message;
                }

                $escape = static fn (string $value): string => htmlspecialchars($value, ENT_QUOTES, 'UTF-8');
                $brandColor = '#0f172a';
                $accentColor = '#6366f1';
                $displayName = $name !== '' ? $name : 'te';
                $htmlMessage = '<!doctype html>';
                $htmlMessage .= '<html lang="it"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1">';
                $htmlMessage .= '<title>' . $escape($appName) . '</title>';
                $htmlMessage .= '</head><body style="margin:0;padding:0;background:#0b1120;font-family:Segoe UI,Roboto,Helvetica,Arial,sans-serif;color:#e5e7eb;">';
                $htmlMessage .= '<table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="background:linear-gradient(135deg,#0b1120 0%,#111827 40%,#1f2937 100%);min-height:100vh;padding:48px 16px;">';
                $htmlMessage .= '<tr><td align="center">';
                $htmlMessage .= '<table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="max-width:720px;background:#0f172a;border-radius:24px;overflow:hidden;box-shadow:0 28px 70px rgba(15,23,42,0.45);">';
                $htmlMessage .= '<tr><td style="padding:32px 32px;background:linear-gradient(120deg,' . $brandColor . ' 0%,#1e293b 60%,#312e81 100%);color:#ffffff;">';
                $htmlMessage .= '<p style="margin:0 0 10px;font-size:12px;letter-spacing:0.12em;text-transform:uppercase;opacity:0.7;">Coresuite Express</p>';
                $htmlMessage .= '<h1 style="margin:0;font-size:24px;font-weight:700;">' . $escape($appName) . '</h1>';
                $htmlMessage .= '<p style="margin:8px 0 0;font-size:15px;opacity:0.85;">Richiesta informazioni piani</p>';
                $htmlMessage .= '</td></tr>';
                $htmlMessage .= '<tr><td style="padding:32px;">';
                $htmlMessage .= '<p style="margin:0 0 14px;font-size:18px;color:#f8fafc;">Ciao <strong>' . $escape($displayName) . '</strong>,</p>';
                $htmlMessage .= '<p style="margin:0 0 24px;color:#cbd5f5;">Ecco il riepilogo aggiornato dei piani disponibili con i relativi costi.</p>';
                $htmlMessage .= '<table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="border-collapse:separate;border-spacing:0 14px;">';
                $htmlMessage .= '<tr><td style="background:#111827;border:1px solid #1f2937;border-radius:16px;padding:18px 20px;">';
                $htmlMessage .= '<p style="margin:0 0 6px;font-size:16px;font-weight:600;color:#f8fafc;">Piano Start</p>';
                $htmlMessage .= '<p style="margin:0 0 10px;font-size:13px;color:#94a3b8;">12 mesi · max 1 cassiere</p>';
                $htmlMessage .= '<p style="margin:0 0 12px;font-size:14px;color:#e2e8f0;">Dashboard, Magazzino SIM, Prodotti, Lista prodotti, Clienti, Listini, Nuova vendita, Storico vendite, Guida completa, Impostazioni.</p>';
                $htmlMessage .= '<span style="display:inline-block;background:#0f172a;border:1px solid #334155;border-radius:999px;padding:6px 12px;font-size:13px;color:#e2e8f0;">€ 550</span>';
                $htmlMessage .= '</td></tr>';
                $htmlMessage .= '<tr><td style="background:#111827;border:1px solid #1f2937;border-radius:16px;padding:18px 20px;">';
                $htmlMessage .= '<p style="margin:0 0 6px;font-size:16px;font-weight:600;color:#f8fafc;">Piano Start Plus</p>';
                $htmlMessage .= '<p style="margin:0 0 10px;font-size:13px;color:#94a3b8;">12 mesi · max 1 cassiere</p>';
                $htmlMessage .= '<p style="margin:0 0 12px;font-size:14px;color:#e2e8f0;">Tutto del Start + Report, Richieste supporto, Ordini store.</p>';
                $htmlMessage .= '<span style="display:inline-block;background:#0f172a;border:1px solid #334155;border-radius:999px;padding:6px 12px;font-size:13px;color:#e2e8f0;">€ 650</span>';
                $htmlMessage .= '</td></tr>';
                $htmlMessage .= '<tr><td style="background:#111827;border:1px solid #1f2937;border-radius:16px;padding:18px 20px;">';
                $htmlMessage .= '<p style="margin:0 0 6px;font-size:16px;font-weight:600;color:#f8fafc;">Piano Core</p>';
                $htmlMessage .= '<p style="margin:0 0 10px;font-size:13px;color:#94a3b8;">24 mesi · max 2 cassieri</p>';
                $htmlMessage .= '<p style="margin:0 0 12px;font-size:14px;color:#e2e8f0;">Tutto del Start Plus + Contratti energia, Report avanzati (KPI), Supporto prioritario.</p>';
                $htmlMessage .= '<span style="display:inline-block;background:#0f172a;border:1px solid #334155;border-radius:999px;padding:6px 12px;font-size:13px;color:#e2e8f0;">€ 850</span>';
                $htmlMessage .= '</td></tr>';
                $htmlMessage .= '<tr><td style="background:#111827;border:1px solid #1f2937;border-radius:16px;padding:18px 20px;">';
                $htmlMessage .= '<p style="margin:0 0 6px;font-size:16px;font-weight:600;color:#f8fafc;">Piano Business</p>';
                $htmlMessage .= '<p style="margin:0 0 10px;font-size:13px;color:#94a3b8;">36 mesi · max 4 cassieri</p>';
                $htmlMessage .= '<p style="margin:0 0 12px;font-size:14px;color:#e2e8f0;">Tutto del Core + Report personalizzati, SLA dedicato, onboarding/training, integrazioni avanzate.</p>';
                $htmlMessage .= '<span style="display:inline-block;background:#0f172a;border:1px solid #334155;border-radius:999px;padding:6px 12px;font-size:13px;color:#e2e8f0;">€ 1200</span>';
                $htmlMessage .= '</td></tr>';
                $htmlMessage .= '</table>';

                if ($company !== '' || $message !== '') {
                    $htmlMessage .= '<div style="border:1px dashed #334155;border-radius:16px;padding:16px 18px;margin:24px 0 20px;background:#0b1120;">';
                    if ($name !== '') {
                        $htmlMessage .= '<p style="margin:0 0 8px;font-size:12px;color:#94a3b8;">Nome e cognome</p>';
                        $htmlMessage .= '<p style="margin:0 0 12px;font-size:15px;color:#f8fafc;font-weight:600;">' . $escape($name) . '</p>';
                    }
                    if ($company !== '') {
                        $htmlMessage .= '<p style="margin:0 0 8px;font-size:12px;color:#94a3b8;">Azienda</p>';
                        $htmlMessage .= '<p style="margin:0 0 12px;font-size:15px;color:#f8fafc;font-weight:600;">' . $escape($company) . '</p>';
                    }
                    if ($message !== '') {
                        $htmlMessage .= '<p style="margin:0 0 8px;font-size:12px;color:#94a3b8;">Messaggio</p>';
                        $htmlMessage .= '<p style="margin:0;font-size:14px;color:#e2e8f0;">' . nl2br($escape($message)) . '</p>';
                    }
                    $htmlMessage .= '</div>';
                }

                $htmlMessage .= '<a href="mailto:' . $escape($resendFrom ?? 'support@coresuite.test') . '" style="display:inline-block;background:' . $accentColor . ';color:#ffffff;text-decoration:none;padding:12px 22px;border-radius:999px;font-weight:600;box-shadow:0 10px 30px rgba(99,102,241,0.35);">Richiedi una demo</a>';
                $htmlMessage .= '</td></tr>';
                $htmlMessage .= '<tr><td style="padding:22px 28px;border-top:1px solid #1f2937;background:#0b1120;color:#94a3b8;font-size:12px;">';
                $htmlMessage .= 'Se non hai richiesto queste informazioni, ignora questa email.';
                $htmlMessage .= '</td></tr>';
                $htmlMessage .= '</table>';
                $htmlMessage .= '</td></tr></table>';
                $htmlMessage .= '</body></html>';

                $sent = sendGuideSupportEmail(
                    $email,
                    $subject,
                    implode("\n", $lines),
                    $resendApiKey,
                    $resendFrom,
                    $resendFromName,
                    $htmlMessage
                );

                $_SESSION['landing_feedback'] = $sent
                    ? ['success' => true, 'message' => 'Ti abbiamo inviato una mail con tutte le informazioni sui piani.']
                    : ['success' => false, 'message' => 'Invio email non riuscito.', 'error' => 'Riprova tra qualche minuto.'];
            } else {
                $_SESSION['landing_feedback'] = [
                    'success' => true,
                    'message' => 'Richiesta inviata correttamente. Ti ricontatteremo a breve.',
                ];
            }

            header('Location: index.php?page=landing#contatto');
            exit;
        }
        render('landing', [
            'pageTitle' => 'Coresuite Express - Gestionale multi-tenant',
            'currentUser' => $currentUser,
            'feedback' => is_array($landingFeedback) ? $landingFeedback : null,
            'oldInput' => is_array($landingOldInput) ? $landingOldInput : null,
        ], false);
        break;
    case 'dashboard':
        $period = $_GET['period'] ?? 'day';
        if (!in_array($period, ['day', 'month', 'year'], true)) {
            $period = 'day';
        }

        $metrics = getDashboardMetrics($pdo, $period);
        $providerInsights = $stockMonitorService->getProviderInsights();
        $stockAlerts = $stockMonitorService->getOpenAlerts();
        $productInsights = $stockMonitorService->getProductInsights();
        $productAlerts = $stockMonitorService->getOpenProductAlerts();
        $lowStockCount = 0;
        $lowStockNames = [];
        foreach ($providerInsights as $insight) {
            if (!empty($insight['below_threshold'])) {
                $lowStockCount++;
                if (!empty($insight['provider_name'])) {
                    $lowStockNames[] = (string) $insight['provider_name'];
                }
            }
        }
        $metrics['low_stock_providers'] = $lowStockCount;
        $lowStockProductNames = [];
        foreach ($productInsights as $productInfo) {
            if (!empty($productInfo['below_threshold']) && !empty($productInfo['product_name'])) {
                $lowStockProductNames[] = (string) $productInfo['product_name'];
            }
        }
        $metrics['low_stock_products'] = count($lowStockProductNames);
        $stockRiskSummary = buildStockRiskSummary($providerInsights);
        $productRiskSummary = buildProductStockRiskSummary($productInsights);
        $nextSteps = buildDashboardNextSteps(
            $metrics,
            $providerInsights,
            $productInsights,
            $metrics['campaign_performance'] ?? ['items' => []],
            $stockAlerts,
            $productAlerts
        );
        $operationalPulse = buildOperationalPulse(
            $metrics,
            $stockAlerts,
            $productAlerts,
            $providerInsights,
            $productInsights,
            $metrics['campaign_performance'] ?? ['items' => []],
            $metrics['support_summary'] ?? [],
            $metrics['billing'] ?? []
        );
        render('dashboard', [
            'metrics' => $metrics,
            'stockAlerts' => $stockAlerts,
            'providerInsights' => $providerInsights,
            'productInsights' => $productInsights,
            'productAlerts' => $productAlerts,
            'selectedPeriod' => $period,
            'currentUser' => $currentUser,
            'lowStockProviders' => $lowStockNames,
            'lowStockProducts' => $lowStockProductNames,
            'stockRiskSummary' => $stockRiskSummary,
            'productRiskSummary' => $productRiskSummary,
            'nextSteps' => $nextSteps,
            'operationalPulse' => $operationalPulse,
        ]);
        break;

    case 'reports':
        $reportData = $reportsController->summary($_GET['view'] ?? 'daily', $_GET);
        render('reports', [
            'report' => $reportData,
            'currentUser' => $currentUser,
            'filters' => $reportData['filters'] ?? [],
            'filterOptions' => $reportData['filter_options'] ?? [],
            'view' => $reportData['granularity'] ?? 'daily',
            'pageTitle' => 'Report vendite',
        ]);
        break;

    case 'sim_stock':
        $feedback = $_SESSION['sim_stock_feedback'] ?? null;
        unset($_SESSION['sim_stock_feedback']);
        $initialToasts = [];
        if (is_array($feedback)) {
            $toast = toastFromFeedback($feedback, [
                'type' => ($feedback['success'] ?? false) ? 'reorder' : 'danger',
                'title' => ($feedback['success'] ?? false) ? 'SIM registrata' : 'Errore magazzino SIM',
                'duration' => ($feedback['success'] ?? false) ? 6000 : 0,
            ]);
            if ($toast !== null) {
                $initialToasts[] = $toast;
            }
        }

        if ($method === 'GET' && ($_GET['action'] ?? '') === 'refresh') {
            $stockPage = isset($_GET['page_no']) ? max((int) $_GET['page_no'], 1) : 1;
            $stockPerPage = isset($_GET['per_page']) ? max(1, min((int) $_GET['per_page'], 50)) : 7;
            $stockSearch = isset($_GET['search']) ? trim((string) $_GET['search']) : null;
            if ($stockSearch === '') {
                $stockSearch = null;
            }
            $stockList = $iccidController->listPaginated($stockPage, $stockPerPage, null, $stockSearch);
            jsonResponse([
                'success' => true,
                'payload' => [
                    'rows' => $stockList['rows'],
                    'pagination' => $stockList['pagination'],
                ],
            ]);
        }

        if ($method === 'POST') {
            $result = $iccidController->create($_POST);

            if (isAjaxRequest()) {
                $status = $result['success'] ? 200 : 422;
                $stockPage = isset($_POST['page_no']) ? max((int) $_POST['page_no'], 1) : 1;
                $stockPerPage = isset($_POST['per_page']) ? max(1, min((int) $_POST['per_page'], 50)) : 7;
                $stockList = $iccidController->listPaginated($stockPage, $stockPerPage);
                jsonResponse([
                    'success' => $result['success'],
                    'message' => $result['message'],
                    'error' => $result['error'] ?? null,
                    'payload' => [
                        'rows' => $stockList['rows'],
                        'pagination' => $stockList['pagination'],
                    ],
                ], $status);
            }

            $_SESSION['sim_stock_feedback'] = $result;
            header('Location: index.php?page=sim_stock');
            exit;
        }
        $stockPage = isset($_GET['page_no']) ? max((int) $_GET['page_no'], 1) : 1;
        $stockPerPage = 7;
        $stockSearch = isset($_GET['search']) ? trim((string) $_GET['search']) : null;
        if ($stockSearch === '') {
            $stockSearch = null;
        }
        $stockList = $iccidController->listPaginated($stockPage, $stockPerPage, null, $stockSearch);
        render('sim_stock', [
            'providers' => $iccidController->providers(),
            'stock' => $stockList['rows'],
            'pagination' => $stockList['pagination'],
            'currentUser' => $currentUser,
            'searchTerm' => $stockSearch ?? '',
            'initialToasts' => $initialToasts,
        ]);
        break;

    case 'products':
        $feedbackProducts = $_SESSION['products_feedback'] ?? null;
        unset($_SESSION['products_feedback']);
        $initialToasts = [];
        if (is_array($feedbackProducts)) {
            $toast = toastFromFeedback($feedbackProducts, [
                'type' => ($feedbackProducts['success'] ?? false) ? 'store' : 'danger',
                'title' => ($feedbackProducts['success'] ?? false) ? 'Catalogo aggiornato' : 'Errore catalogo prodotti',
                'duration' => ($feedbackProducts['success'] ?? false) ? 6000 : 0,
            ]);
            if ($toast !== null) {
                $initialToasts[] = $toast;
            }
        }
        $editId = isset($_GET['edit']) ? max((int) $_GET['edit'], 0) : 0;
        $productToEdit = null;

        if ($editId > 0) {
            $productToEdit = $productController->find($editId);
            if ($productToEdit === null) {
                $feedbackProducts = [
                    'success' => false,
                    'message' => 'Prodotto non trovato.',
                    'errors' => ['Il prodotto selezionato non è più presente a catalogo.'],
                ];
                $toast = toastFromFeedback($feedbackProducts, [
                    'type' => 'warning',
                    'title' => 'Prodotto non trovato',
                    'duration' => 0,
                ]);
                if ($toast !== null) {
                    $initialToasts[] = $toast;
                }
            }
        }

        if ($method === 'POST') {
            $currentUserId = null;
            if (is_array($currentUser) && isset($currentUser['id'])) {
                $candidate = (int) $currentUser['id'];
                if ($candidate > 0) {
                    $currentUserId = $candidate;
                }
            }
            $action = $_POST['action'] ?? 'create';
            if ($action === 'update') {
                $productId = isset($_POST['product_id']) ? (int) $_POST['product_id'] : 0;
                $result = $productController->update($productId, $_POST, $currentUserId);
                if ($result['success'] ?? false) {
                    $_SESSION['products_list_feedback'] = $result;
                    header('Location: index.php?page=products_list');
                } else {
                    $_SESSION['products_feedback'] = $result;
                    header('Location: index.php?page=products&edit=' . max($productId, 0));
                }
                exit;
            }

            $result = $productController->create($_POST, $currentUserId);
            $_SESSION['products_feedback'] = $result;
            header('Location: index.php?page=products');
            exit;
        }

        render('products', [
            'currentUser' => $currentUser,
            'pageTitle' => 'Catalogo prodotti',
            'editingProduct' => $productToEdit,
            'initialToasts' => $initialToasts,
        ]);
        break;

    case 'products_list':
        $feedbackList = $_SESSION['products_list_feedback'] ?? null;
        unset($_SESSION['products_list_feedback']);
        $initialToasts = [];
        if (is_array($feedbackList)) {
            $toast = toastFromFeedback($feedbackList, [
                'type' => ($feedbackList['success'] ?? false) ? 'store' : 'danger',
                'title' => ($feedbackList['success'] ?? false) ? 'Catalogo aggiornato' : 'Errore lista prodotti',
                'duration' => ($feedbackList['success'] ?? false) ? 6000 : 0,
            ]);
            if ($toast !== null) {
                $initialToasts[] = $toast;
            }
        }

        if ($method === 'POST') {
            $action = $_POST['action'] ?? '';
            $productId = isset($_POST['product_id']) ? (int) $_POST['product_id'] : 0;
            if ($action === 'delete_product') {
                $result = $productController->delete($productId);
            } elseif ($action === 'restock_product') {
                $result = $productController->restock($productId);
            } else {
                $result = [
                    'success' => false,
                    'message' => 'Azione non riconosciuta.',
                    'errors' => ['Richiesta non valida per la lista prodotti.'],
                ];
            }
            $_SESSION['products_list_feedback'] = $result;
            header('Location: index.php?page=products_list');
            exit;
        }

        $productsPage = isset($_GET['page_no']) ? max((int) $_GET['page_no'], 1) : 1;
        $productsPerPage = 7;
        if (isset($_GET['per_page'])) {
            $requested = max(1, min((int) $_GET['per_page'], 50));
            if ($requested !== $productsPerPage) {
                $productsPerPage = $requested;
            }
        }

        $productsSearch = isset($_GET['search']) ? trim((string) $_GET['search']) : null;
        if ($productsSearch === '') {
            $productsSearch = null;
        }
        $productsList = $productController->listPaginated($productsPage, $productsPerPage, $productsSearch);
        render('products_list', [
            'products' => $productsList['rows'],
            'pagination' => $productsList['pagination'],
            'currentUser' => $currentUser,
            'pageTitle' => 'Lista prodotti',
            'searchTerm' => $productsSearch ?? '',
            'initialToasts' => $initialToasts,
        ]);
        break;

    case 'customers':
        $feedbackCustomers = $_SESSION['customers_feedback'] ?? null;
        unset($_SESSION['customers_feedback']);

        $initialToasts = [];
        if (is_array($feedbackCustomers)) {
            $toast = toastFromFeedback($feedbackCustomers);
            if ($toast !== null) {
                $detailLines = [];
                if (!empty($feedbackCustomers['success'])) {
                    $portalInfo = $feedbackCustomers['portal_account'] ?? null;
                    if (is_array($portalInfo)) {
                        $portalStatus = (string) ($portalInfo['status'] ?? '');
                        $portalEmail = isset($portalInfo['email']) ? trim((string) $portalInfo['email']) : '';
                        $portalPassword = isset($portalInfo['password']) ? trim((string) $portalInfo['password']) : '';

                        if ($portalStatus === 'created' && $portalPassword !== '') {
                            $detailLines[] = 'Credenziali area clienti generate:';
                            if ($portalEmail !== '') {
                                $detailLines[] = 'Email: ' . $portalEmail;
                            }
                            $detailLines[] = 'Password temporanea: ' . $portalPassword;
                            $detailLines[] = 'Condividi la password con il cliente e invita al cambio al primo accesso.';
                        } elseif ($portalStatus === 'updated' && $portalEmail !== '') {
                            $detailLines[] = 'Email di accesso area clienti aggiornata a ' . $portalEmail . '.';
                        } elseif ($portalStatus === 'resent') {
                            if ($portalEmail !== '') {
                                $detailLines[] = 'Nuove credenziali generate per ' . $portalEmail . '.';
                            } else {
                                $detailLines[] = 'Nuove credenziali generate.';
                            }
                            if ($portalPassword !== '') {
                                $detailLines[] = 'Password temporanea: ' . $portalPassword;
                            }
                            if (array_key_exists('email_sent', $portalInfo)) {
                                $detailLines[] = !empty($portalInfo['email_sent'])
                                    ? 'Email inviata automaticamente al cliente.'
                                    : 'Invio automatico non riuscito. Condividi le credenziali manualmente.';
                            }
                        }
                    }

                    $invitationInfo = $feedbackCustomers['invitation'] ?? null;
                    if (is_array($invitationInfo)) {
                        $invitationEmail = isset($invitationInfo['email']) ? trim((string) $invitationInfo['email']) : '';
                        if ($invitationEmail !== '') {
                            $detailLines[] = 'Link di attivazione generato per ' . $invitationEmail . '.';
                        }
                        if (!empty($invitationInfo['activation_link'])) {
                            $detailLines[] = 'URL attivazione: ' . trim((string) $invitationInfo['activation_link']);
                        }
                        if (!empty($invitationInfo['token'])) {
                            $detailLines[] = 'Codice invito: ' . trim((string) $invitationInfo['token']);
                        }
                        if (array_key_exists('email_sent', $invitationInfo)) {
                            $detailLines[] = !empty($invitationInfo['email_sent'])
                                ? 'Email inviata automaticamente.'
                                : 'Email non inviata, condividi il link manualmente.';
                        }
                    }
                }

                if ($detailLines !== []) {
                    $messageLines = [];
                    if (!empty($toast['message'])) {
                        $messageLines[] = (string) $toast['message'];
                    }
                    foreach ($detailLines as $line) {
                        $trimmedLine = trim((string) $line);
                        if ($trimmedLine !== '') {
                            $messageLines[] = $trimmedLine;
                        }
                    }
                    if ($messageLines !== []) {
                        $toast['message'] = implode("\n", $messageLines);
                    }
                    $toast['duration'] = 0;
                }

                $initialToasts[] = $toast;
            }
        }

        $searchTerm = isset($_GET['search']) ? trim((string) $_GET['search']) : '';
        $perPage = 10;
        if (isset($_GET['per_page'])) {
            $requestedPerPage = max(1, min((int) $_GET['per_page'], 50));
            if ($requestedPerPage !== $perPage) {
                $perPage = $requestedPerPage;
            }
        }

        if ($method === 'POST') {
            $action = $_POST['action'] ?? '';
            $customerId = isset($_POST['customer_id']) ? (int) $_POST['customer_id'] : 0;
            $searchTerm = isset($_POST['search_term']) ? trim((string) $_POST['search_term']) : $searchTerm;
            $pageNo = isset($_POST['page_no']) ? max((int) $_POST['page_no'], 1) : null;
            $perPagePost = isset($_POST['per_page']) ? max((int) $_POST['per_page'], 1) : null;

            if ($action === 'create_customer') {
                $result = $customerController->create($_POST);
            } elseif ($action === 'update_customer') {
                $result = $customerController->update($customerId, $_POST);
            } elseif ($action === 'delete_customer') {
                $result = $customerController->delete($customerId);
            } elseif ($action === 'resend_portal_credentials') {
                $result = $customerController->resendPortalCredentials($customerId);
            } elseif ($action === 'send_portal_invitation') {
                $result = $customerController->sendPortalInvitation($customerId);
            } else {
                $result = [
                    'success' => false,
                    'message' => 'Azione non riconosciuta.',
                ];
            }

            $_SESSION['customers_feedback'] = $result;

            $redirectParams = ['page' => 'customers'];
            if ($searchTerm !== '') {
                $redirectParams['search'] = $searchTerm;
            }
            if ($pageNo !== null) {
                $redirectParams['page_no'] = $pageNo;
            }
            if ($perPagePost !== null) {
                $redirectParams['per_page'] = min($perPagePost, 50);
            }
            if ($action === 'update_customer' && !($result['success'] ?? false) && $customerId > 0) {
                $redirectParams['edit'] = $customerId;
            }

            header('Location: index.php?' . http_build_query($redirectParams));
            exit;
        }

        $editId = isset($_GET['edit']) ? max((int) $_GET['edit'], 0) : 0;
        $customerToEdit = null;
        if ($editId > 0) {
            $customerToEdit = $customerController->find($editId);
            if ($customerToEdit === null) {
                $notFoundToast = toastFromFeedback([
                    'success' => false,
                    'message' => 'Cliente non trovato.',
                    'errors' => ['Il cliente selezionato non è più disponibile.'],
                ], ['duration' => 0]);
                if ($notFoundToast !== null) {
                    $initialToasts[] = $notFoundToast;
                }
            }
        }

        $customersPage = isset($_GET['page_no']) ? max((int) $_GET['page_no'], 1) : 1;
        $customersList = $customerController->listPaginated($customersPage, $perPage, $searchTerm !== '' ? $searchTerm : null);

        $buildCustomersPageUrl = static function (int $pageNo, string $search, int $perPage) {
            $params = [
                'page' => 'customers',
                'page_no' => max(1, $pageNo),
            ];
            if ($search !== '') {
                $params['search'] = $search;
            }
            if ($perPage !== 10) {
                $params['per_page'] = $perPage;
            }

            return 'index.php?' . http_build_query($params);
        };

        render('customers', [
            'customers' => $customersList['rows'],
            'pagination' => $customersList['pagination'],
            'currentUser' => $currentUser,
            'pageTitle' => 'Clienti',
            'searchTerm' => $searchTerm,
            'editingCustomer' => $customerToEdit,
            'perPage' => $perPage,
            'buildPageUrl' => $buildCustomersPageUrl,
            'initialToasts' => $initialToasts,
        ]);
        break;

    case 'profile':
        if ($currentUser === null) {
            header('Location: index.php?page=login');
            exit;
        }

        if ($method === 'POST' && (($_POST['action'] ?? '') === 'update_password')) {
            $result = $userService->updateOwnPassword((int) ($currentUser['id'] ?? 0), $_POST);
            $_SESSION['profile_password_feedback'] = $result;
            header('Location: index.php?page=profile');
            exit;
        }

        $profileUser = $userService->findUser((int) ($currentUser['id'] ?? 0));
        if ($profileUser === null) {
            pushFlashToast([
                'type' => 'danger',
                'title' => 'Profilo non disponibile',
                'message' => 'Impossibile caricare il profilo utente corrente.',
                'duration' => 0,
            ]);
            header('Location: index.php?page=dashboard');
            exit;
        }

        $roleNameRaw = (string) ($profileUser['role_name'] ?? '');
        $roleLabel = formatRoleLabel($roleNameRaw);
        $roleKey = strtolower(str_replace(' ', '_', $roleNameRaw));
        $shortcuts = resolveProfileShortcuts($roleKey);
        $salesSummary = buildUserProfileSalesSummary($pdo, (int) $profileUser['id']);
        $roleSummary = resolveRoleSummary($roleKey);
        $passwordFeedback = $_SESSION['profile_password_feedback'] ?? null;
        unset($_SESSION['profile_password_feedback']);

        render('profile', [
            'currentUser' => $currentUser,
            'pageTitle' => 'Profilo utente',
            'profile' => $profileUser,
            'roleLabel' => $roleLabel,
            'roleSummary' => $roleSummary,
            'shortcuts' => $shortcuts,
            'salesSummary' => $salesSummary,
            'passwordFeedback' => is_array($passwordFeedback) ? $passwordFeedback : null,
        ]);
        break;

    case 'guide':
        $guideFeedback = $_SESSION['guide_feedback'] ?? null;
        unset($_SESSION['guide_feedback']);

        if ($method === 'POST') {
            $action = $_POST['action'] ?? '';
            if ($action === 'send_guide_support') {
                $supportRecipient = 'ag.servizi16@gmail.com';
                $username = (string) ($currentUser['username'] ?? 'utente');
                $fullName = trim((string) ($currentUser['fullname'] ?? ''));
                $subject = 'Richiesta supporto da guida Coresuite Express';
                $message = "Richiesta supporto dalla pagina Guida.\n";
                $message .= 'Username: ' . $username . "\n";
                if ($fullName !== '') {
                    $message .= 'Nome completo: ' . $fullName . "\n";
                }
                $message .= 'Data: ' . date('Y-m-d H:i:s');

                $sent = sendGuideSupportEmail(
                    $supportRecipient,
                    $subject,
                    $message,
                    $resendApiKey,
                    $resendFrom,
                    $resendFromName
                );

                $_SESSION['guide_feedback'] = $sent
                    ? ['success' => true, 'message' => 'Email inviata correttamente al supporto.']
                    : ['success' => false, 'message' => 'Invio email non riuscito.', 'error' => 'Riprova tra qualche minuto.'];
                header('Location: index.php?page=guide');
                exit;
            }
        }

        render('guide', [
            'currentUser' => $currentUser,
            'pageTitle' => 'Guida completa',
            'feedback' => $guideFeedback,
        ]);
        break;

    case 'tenants':
        if (!$authService->hasRole('admin')) {
            header('Location: index.php?page=dashboard');
            exit;
        }

        $tenantFeedback = $_SESSION['tenants_feedback'] ?? null;
        unset($_SESSION['tenants_feedback']);

        if ($method === 'POST') {
            $action = $_POST['action'] ?? '';

            if ($action === 'create_tenant') {
                $result = $tenantService->createTenant($_POST);
            } elseif ($action === 'update_tenant') {
                $tenantId = (int) ($_POST['tenant_id'] ?? 0);
                $result = $tenantService->updateTenant($tenantId, $_POST);
            } elseif ($action === 'toggle_tenant') {
                $tenantId = (int) ($_POST['tenant_id'] ?? 0);
                $enabled = (int) ($_POST['enabled'] ?? 0) === 1;
                $result = $tenantService->toggleTenant($tenantId, $enabled);
            } elseif ($action === 'resend_tenant_credentials') {
                $tenantId = (int) ($_POST['tenant_id'] ?? 0);
                $result = $userService->resendTenantCredentials($tenantId);
            } else {
                $result = [
                    'success' => false,
                    'message' => 'Operazione non riconosciuta.',
                ];
            }

            $_SESSION['tenants_feedback'] = $result;
            header('Location: index.php?page=tenants');
            exit;
        }

        $tenants = $tenantService->listTenants();

        render('tenants', [
            'currentUser' => $currentUser,
            'pageTitle' => 'Tenant',
            'feedback' => $tenantFeedback,
            'tenants' => $tenants,
        ]);
        break;

    case 'licenses':
        if (!$authService->hasRole('admin')) {
            header('Location: index.php?page=dashboard');
            exit;
        }

        $licenseFeedback = $_SESSION['licenses_feedback'] ?? null;
        unset($_SESSION['licenses_feedback']);
        $licenseGeneratedCode = $_SESSION['license_generated_code'] ?? null;
        unset($_SESSION['license_generated_code']);

        if ($method === 'POST') {
            $action = $_POST['action'] ?? '';

            if ($action === 'assign_license') {
                $result = $tenantService->assignLicense($_POST);
            } elseif ($action === 'revoke_tenant_license') {
                $assignmentId = (int) ($_POST['assignment_id'] ?? 0);
                $result = $tenantService->revokeTenantLicense($assignmentId);
            } elseif ($action === 'renew_tenant_license') {
                $assignmentId = (int) ($_POST['assignment_id'] ?? 0);
                $result = $tenantService->renewTenantLicense($assignmentId);
            } elseif ($action === 'update_assignment_payment') {
                $assignmentId = (int) ($_POST['assignment_id'] ?? 0);
                $paid = isset($_POST['payment_status']) && (string) $_POST['payment_status'] === 'paid';
                $result = $tenantService->updateAssignmentPayment($assignmentId, $paid);
            } elseif ($action === 'create_license') {
                $label = isset($_POST['license_label']) ? trim((string) $_POST['license_label']) : null;
                $maxUsers = isset($_POST['license_max_users']) ? (int) $_POST['license_max_users'] : 1;
                $termMonths = isset($_POST['license_term_months']) ? (int) $_POST['license_term_months'] : 0;
                $result = $licenseService->createLicense($label, $maxUsers, $termMonths);
                if (($result['success'] ?? false) && isset($result['code'])) {
                    $_SESSION['license_generated_code'] = [
                        'code' => (string) $result['code'],
                        'label' => $label !== '' ? $label : null,
                    ];
                }
            } elseif ($action === 'toggle_license') {
                $licenseId = (int) ($_POST['license_id'] ?? 0);
                $enabled = (int) ($_POST['enabled'] ?? 0) === 1;
                $result = $licenseService->toggleLicense($licenseId, $enabled);
            } else {
                $result = [
                    'success' => false,
                    'message' => 'Operazione non riconosciuta.',
                ];
            }

            $_SESSION['licenses_feedback'] = $result;
            header('Location: index.php?page=licenses');
            exit;
        }

        $tenants = $tenantService->listTenants();
        $licenses = $licenseService->listLicenses();
        $assignments = $tenantService->listTenantLicenses();

        $selectedLicenseId = isset($_GET['license_id']) ? (int) $_GET['license_id'] : 0;
        $selectedAssignmentId = isset($_GET['assignment_id']) ? (int) $_GET['assignment_id'] : 0;

        render('licenses', [
            'currentUser' => $currentUser,
            'pageTitle' => 'Licenze',
            'feedback' => $licenseFeedback,
            'tenants' => $tenants,
            'licenses' => $licenses,
            'assignments' => $assignments,
            'licenseGeneratedCode' => $licenseGeneratedCode,
            'selectedLicenseId' => $selectedLicenseId,
            'selectedAssignmentId' => $selectedAssignmentId,
        ]);
        break;

    case 'terms':
        render('terms', [
            'currentUser' => $currentUser,
            'pageTitle' => 'Termini e condizioni',
        ]);
        break;

    case 'privacy':
        $policy = $privacyPolicyService->getActivePolicy();
        render('privacy', [
            'currentUser' => $currentUser,
            'pageTitle' => 'Privacy policy',
            'policy' => $policy,
        ]);
        break;

    case 'settings':
        $feedback = $_SESSION['settings_feedback'] ?? null;
        unset($_SESSION['settings_feedback']);
        $pdaSettingsFeedback = $_SESSION['pda_settings_feedback'] ?? null;
        unset($_SESSION['pda_settings_feedback']);
        $receiptSettingsFeedback = $_SESSION['receipt_settings_feedback'] ?? null;
        unset($_SESSION['receipt_settings_feedback']);
        $energySettingsFeedback = $_SESSION['energy_settings_feedback'] ?? null;
        unset($_SESSION['energy_settings_feedback']);
        $licenseSettingsFeedback = $_SESSION['license_settings_feedback'] ?? null;
        unset($_SESSION['license_settings_feedback']);
        $licenseGeneratedCode = $_SESSION['license_generated_code'] ?? null;
        unset($_SESSION['license_generated_code']);
        $ssoFeedback = $_SESSION['settings_sso_feedback'] ?? null;
        unset($_SESSION['settings_sso_feedback']);
        $ssoSecretPreview = $_SESSION['settings_sso_secret'] ?? null;
        unset($_SESSION['settings_sso_secret']);
        $ssoEnabled = $ssoService->isEnabled();
        $tenantId = isset($currentUser['tenant_id']) ? (int) $currentUser['tenant_id'] : 1;
        $isAdmin = $authService->hasRole('admin');
        $canManageTenantSettings = $isAdmin || $tenantId > 1;
        $pdaSettings = $pdaSettingsService->getSettings();
        $pdaOpen = isset($_GET['pda_open']) || $pdaSettingsFeedback !== null;
        $receiptSettings = $receiptSettingsService->getSettings();
        $receiptOpen = isset($_GET['receipt_open']) || $receiptSettingsFeedback !== null;
        $energyProviders = $canManageTenantSettings ? $energyProviderService->listProviders() : [];
        $energyOpen = isset($_GET['energy_open']) || $energySettingsFeedback !== null;
        $energyOffersImportStatus = loadEnergyOffersImportStatus();
        $licensesOpen = isset($_GET['licenses_open']) || $licenseSettingsFeedback !== null || $licenseGeneratedCode !== null;
        $licenseFocusId = isset($_GET['license_id']) ? (int) $_GET['license_id'] : 0;
        $licenses = [];
        $licenseActivations = [];
        if ($isAdmin) {
            $licenses = $licenseService->listLicenses();
            if ($licenseFocusId > 0) {
                $licenseActivations = $licenseService->listActivations($licenseFocusId);
                $licensesOpen = true;
            }
        }
        $tenantsList = $isAdmin ? $tenantService->listTenants() : [];

        if ($isAdmin) {
            maybeScheduleEnergyOffersImport();
        }

        $operatorEdit = null;
        $operatorEditForm = null;
        $operatorsOpenOverride = null;
        $operatorEditId = 0;
        $providersOpenOverride = null;

        if ($canManageTenantSettings) {
            if (isset($_SESSION['settings_operator_form']) && is_array($_SESSION['settings_operator_form'])) {
                $storedOperatorForm = $_SESSION['settings_operator_form'];
                unset($_SESSION['settings_operator_form']);
                $operatorEditId = isset($storedOperatorForm['id']) ? (int) $storedOperatorForm['id'] : 0;
                $operatorEditForm = isset($storedOperatorForm['form']) && is_array($storedOperatorForm['form'])
                    ? $storedOperatorForm['form']
                    : null;
                $operatorsOpenOverride = true;
            }

            if (isset($_GET['operators_open'])) {
                $operatorsOpenOverride = true;
            }

            if (isset($_GET['providers_open'])) {
                $providersOpenOverride = true;
            }

            if (isset($_GET['edit_operator'])) {
                $operatorEditId = max((int) $_GET['edit_operator'], 0);
                if ($operatorEditId > 0) {
                    $operatorsOpenOverride = true;
                }
            }
        } else {
            unset($_SESSION['settings_operator_form']);
        }

        $fiscalOpen = isset($_GET['fiscal_open']);
        $ssoOpen = isset($_GET['sso_open']);
        $ssoClients = [];
        if ($ssoEnabled) {
            try {
                $ssoClients = $ssoService->listClients();
            } catch (\Throwable $exception) {
                if ($ssoFeedback === null) {
                    $ssoFeedback = [
                        'success' => false,
                        'message' => 'Impossibile caricare i client SSO: ' . $exception->getMessage(),
                    ];
                }
            }
        }

        if ($method === 'POST') {
            $action = $_POST['action'] ?? '';
            $redirectParams = [];
            $isPdaAction = false;
            $isReceiptAction = false;
            $isEnergyAction = false;
            $isLicenseAction = false;

            if ($action === 'update_threshold') {
                if (!$canManageTenantSettings) {
                    $result = [
                        'success' => false,
                        'message' => 'Operazione non autorizzata.',
                        'error' => 'Solo i responsabili del tenant possono aggiornare le soglie.',
                    ];
                } else {
                    $providerId = (int) ($_POST['provider_id'] ?? 0);
                    $threshold = (int) ($_POST['reorder_threshold'] ?? 0);
                    $result = $stockMonitorService->updateThreshold($providerId, $threshold);
                }
            } elseif ($action === 'create_operator') {
                if (!$canManageTenantSettings) {
                    $result = [
                        'success' => false,
                        'message' => 'Operazione non autorizzata.',
                        'error' => 'Solo i responsabili del tenant possono creare operatori.',
                    ];
                } else {
                    $payload = $_POST;
                    $targetTenantId = $tenantId;
                    $licenseForLimit = null;

                    if ($isAdmin) {
                        $targetTenantId = isset($_POST['operator_tenant_id']) ? (int) $_POST['operator_tenant_id'] : 0;
                        $licenseId = isset($_POST['operator_license_id']) ? (int) $_POST['operator_license_id'] : 0;
                        if ($licenseId > 0) {
                            $licenseForLimit = resolveLicenseById($pdo, $licenseId);
                        }
                    } else {
                        $payload['operator_tenant_id'] = $tenantId;
                        unset($payload['operator_license_id']);
                    }

                    if ($targetTenantId <= 0) {
                        $result = [
                            'success' => false,
                            'message' => 'Impossibile creare l’operatore.',
                            'error' => 'Tenant non valido.',
                        ];
                    } else {
                        if ($licenseForLimit === null) {
                            $licenseForLimit = resolveTenantLicense($pdo, $targetTenantId);
                        }

                        if (is_array($licenseForLimit) && (int) ($licenseForLimit['is_active'] ?? 0) !== 1) {
                            $licenseForLimit = null;
                        }

                        $maxOperators = resolveOperatorLimitForLicense($licenseForLimit);
                        $currentOperators = $userService->listOperators($targetTenantId);
                        $currentCount = is_array($currentOperators) ? count($currentOperators) : 0;

                        if ($maxOperators <= 0) {
                            $result = [
                                'success' => false,
                                'message' => 'Impossibile creare l’operatore.',
                                'error' => 'Licenza non attiva o scaduta per questo tenant.',
                            ];
                        } elseif ($currentCount >= $maxOperators) {
                            $result = [
                                'success' => false,
                                'message' => 'Limite operatori raggiunto.',
                                'error' => 'Il piano attivo consente massimo ' . $maxOperators . ' operatori.',
                            ];
                        } else {
                            $result = $userService->createOperator($payload);
                        }
                    }
                    if (($result['success'] ?? false) && $isAdmin) {
                        $licenseId = isset($_POST['operator_license_id']) ? (int) $_POST['operator_license_id'] : 0;
                        $targetTenantId = isset($_POST['operator_tenant_id']) ? (int) $_POST['operator_tenant_id'] : 0;
                        if ($licenseId > 0 && $targetTenantId > 0) {
                            $assignment = $tenantService->assignLicense([
                                'tenant_id' => $targetTenantId,
                                'license_id' => $licenseId,
                            ]);
                            if (!($assignment['success'] ?? false)) {
                                $result = [
                                    'success' => false,
                                    'message' => 'Operatore creato, ma licenza non assegnata.',
                                    'error' => $assignment['error'] ?? 'Verifica assegnazione licenza.',
                                ];
                            }
                        }
                    }
                }
                if (!($result['success'] ?? false)) {
                    $redirectParams['operators_open'] = 1;
                }
            } elseif ($action === 'update_operator') {
                if (!$canManageTenantSettings) {
                    $result = [
                        'success' => false,
                        'message' => 'Operazione non autorizzata.',
                        'error' => 'Solo i responsabili del tenant possono aggiornare operatori.',
                    ];
                } else {
                    $operatorId = isset($_POST['operator_id']) ? (int) $_POST['operator_id'] : 0;
                    if (!$isAdmin && $operatorId > 0) {
                        $operatorRow = $userService->findUser($operatorId);
                        $operatorTenantId = isset($operatorRow['tenant_id']) ? (int) $operatorRow['tenant_id'] : 0;
                        if ($operatorRow === null || $operatorTenantId !== $tenantId) {
                            $result = [
                                'success' => false,
                                'message' => 'Operazione non autorizzata.',
                                'error' => 'Puoi modificare solo operatori del tuo tenant.',
                            ];
                            $redirectParams['operators_open'] = 1;
                            $_SESSION['settings_feedback'] = $result;
                            header('Location: index.php?' . http_build_query(['page' => 'settings', 'operators_open' => 1]));
                            exit;
                        }
                    }
                    $payload = $_POST;
                    if (!$isAdmin) {
                        $payload['operator_edit_tenant_id'] = $tenantId;
                    }
                    $formData = [
                        'fullname' => trim((string) ($_POST['operator_edit_fullname'] ?? '')),
                        'username' => trim((string) ($_POST['operator_edit_username'] ?? '')),
                        'role_id' => isset($_POST['operator_edit_role']) ? (int) $_POST['operator_edit_role'] : 0,
                        'email' => trim((string) ($_POST['operator_edit_email'] ?? '')),
                        'tenant_id' => $isAdmin
                            ? (int) ($_POST['operator_edit_tenant_id'] ?? 0)
                            : $tenantId,
                    ];
                    $result = $userService->updateOperator($operatorId, $payload);
                    if (!($result['success'] ?? false)) {
                        $_SESSION['settings_operator_form'] = [
                            'id' => $operatorId,
                            'form' => $formData,
                        ];
                        if ($operatorId > 0) {
                            $redirectParams['edit_operator'] = $operatorId;
                        }
                    } else {
                        unset($_SESSION['settings_operator_form']);
                    }
                }
                $redirectParams['operators_open'] = 1;
            } elseif ($action === 'delete_operator') {
                if (!$canManageTenantSettings) {
                    $result = [
                        'success' => false,
                        'message' => 'Operazione non autorizzata.',
                        'error' => 'Solo i responsabili del tenant possono eliminare operatori.',
                    ];
                } else {
                    $operatorId = isset($_POST['operator_id']) ? (int) $_POST['operator_id'] : 0;
                    $actingUserId = isset($currentUser['id']) ? (int) $currentUser['id'] : 0;
                    if (!$isAdmin && $operatorId > 0) {
                        $operatorRow = $userService->findUser($operatorId);
                        $operatorTenantId = isset($operatorRow['tenant_id']) ? (int) $operatorRow['tenant_id'] : 0;
                        if ($operatorRow === null || $operatorTenantId !== $tenantId) {
                            $result = [
                                'success' => false,
                                'message' => 'Operazione non autorizzata.',
                                'error' => 'Puoi eliminare solo operatori del tuo tenant.',
                            ];
                            $redirectParams['operators_open'] = 1;
                            $_SESSION['settings_feedback'] = $result;
                            header('Location: index.php?' . http_build_query(['page' => 'settings', 'operators_open' => 1]));
                            exit;
                        }
                    }
                    $result = $userService->deleteOperator($operatorId, $actingUserId);
                    unset($_SESSION['settings_operator_form']);
                }
                $redirectParams['operators_open'] = 1;
            } elseif ($action === 'create_provider') {
                if (!$canManageTenantSettings) {
                    $result = [
                        'success' => false,
                        'message' => 'Operazione non autorizzata.',
                        'error' => 'Solo i responsabili del tenant possono creare gestori.',
                    ];
                } else {
                    $result = $providerService->createProvider($_POST, (int) ($currentUser['id'] ?? 0));
                }
                $redirectParams['providers_open'] = 1;
            } elseif ($action === 'update_provider') {
                if (!$canManageTenantSettings) {
                    $result = [
                        'success' => false,
                        'message' => 'Operazione non autorizzata.',
                        'error' => 'Solo i responsabili del tenant possono aggiornare i gestori.',
                    ];
                } else {
                    $providerId = isset($_POST['provider_id']) ? (int) $_POST['provider_id'] : 0;
                    $result = $providerService->updateProvider($providerId, $_POST, (int) ($currentUser['id'] ?? 0));
                }
                $redirectParams['providers_open'] = 1;
            } elseif ($action === 'delete_provider') {
                if (!$canManageTenantSettings) {
                    $result = [
                        'success' => false,
                        'message' => 'Operazione non autorizzata.',
                        'error' => 'Solo i responsabili del tenant possono eliminare gestori.',
                    ];
                } else {
                    $providerId = isset($_POST['provider_id']) ? (int) $_POST['provider_id'] : 0;
                    $result = $providerService->deleteProvider($providerId, (int) ($currentUser['id'] ?? 0));
                }
                $redirectParams['providers_open'] = 1;
            } elseif ($action === 'create_energy_provider') {
                $isEnergyAction = true;
                if (!$canManageTenantSettings) {
                    $result = [
                        'success' => false,
                        'message' => 'Operazione non autorizzata.',
                        'error' => 'Solo i responsabili del tenant possono creare gestori energia.',
                    ];
                } else {
                    $result = $energyProviderService->createProvider($_POST, (int) ($currentUser['id'] ?? 0));
                }
                $_SESSION['energy_settings_feedback'] = $result;
                $redirectParams['energy_open'] = 1;
            } elseif ($action === 'update_energy_provider') {
                $isEnergyAction = true;
                if (!$canManageTenantSettings) {
                    $result = [
                        'success' => false,
                        'message' => 'Operazione non autorizzata.',
                        'error' => 'Solo i responsabili del tenant possono aggiornare gestori energia.',
                    ];
                } else {
                    $providerId = isset($_POST['energy_provider_id']) ? (int) $_POST['energy_provider_id'] : 0;
                    $result = $energyProviderService->updateProvider($providerId, $_POST, (int) ($currentUser['id'] ?? 0));
                }
                $_SESSION['energy_settings_feedback'] = $result;
                $redirectParams['energy_open'] = 1;
            } elseif ($action === 'delete_energy_provider') {
                $isEnergyAction = true;
                if (!$canManageTenantSettings) {
                    $result = [
                        'success' => false,
                        'message' => 'Operazione non autorizzata.',
                        'error' => 'Solo i responsabili del tenant possono eliminare gestori energia.',
                    ];
                } else {
                    $providerId = isset($_POST['energy_provider_id']) ? (int) $_POST['energy_provider_id'] : 0;
                    $result = $energyProviderService->deleteProvider($providerId, (int) ($currentUser['id'] ?? 0));
                }
                $_SESSION['energy_settings_feedback'] = $result;
                $redirectParams['energy_open'] = 1;
            } elseif ($action === 'import_energy_offers') {
                $isEnergyAction = true;
                if (!$isAdmin) {
                    $result = [
                        'success' => false,
                        'message' => 'Operazione non autorizzata.',
                        'error' => 'Solo gli amministratori possono importare offerte ARERA.',
                    ];
                } else {
                    $result = runEnergyOffersImport(false);
                }
                $_SESSION['energy_settings_feedback'] = $result;
                $redirectParams['energy_open'] = 1;
            } elseif ($action === 'create_license_key') {
                $isLicenseAction = true;
                if (!$isAdmin) {
                    $result = [
                        'success' => false,
                        'message' => 'Operazione non autorizzata.',
                        'error' => 'Solo gli amministratori possono generare licenze.',
                    ];
                } else {
                    $label = trim((string) ($_POST['license_label'] ?? ''));
                    $maxUsers = isset($_POST['license_max_users']) ? (int) $_POST['license_max_users'] : 1;
                    $termMonths = isset($_POST['license_term_months']) ? (int) $_POST['license_term_months'] : 0;
                    $result = $licenseService->createLicense($label !== '' ? $label : null, $maxUsers, $termMonths);
                    if (($result['success'] ?? false) && isset($result['code'])) {
                        $_SESSION['license_generated_code'] = [
                            'code' => $result['code'],
                            'label' => $label,
                        ];
                    }
                }
                $_SESSION['license_settings_feedback'] = $result;
                $redirectParams['licenses_open'] = 1;
            } elseif ($action === 'toggle_license_key') {
                $isLicenseAction = true;
                if (!$isAdmin) {
                    $result = [
                        'success' => false,
                        'message' => 'Operazione non autorizzata.',
                        'error' => 'Solo gli amministratori possono modificare le licenze.',
                    ];
                } else {
                    $licenseId = isset($_POST['license_id']) ? (int) $_POST['license_id'] : 0;
                    $targetStatus = isset($_POST['target_status']) ? ((int) $_POST['target_status'] === 1) : false;
                    $result = $licenseService->toggleLicense($licenseId, $targetStatus);
                }
                $_SESSION['license_settings_feedback'] = $result;
                $redirectParams['licenses_open'] = 1;
            } elseif ($action === 'revoke_license_activation') {
                $isLicenseAction = true;
                if (!$isAdmin) {
                    $result = [
                        'success' => false,
                        'message' => 'Operazione non autorizzata.',
                        'error' => 'Solo gli amministratori possono revocare attivazioni.',
                    ];
                } else {
                    $activationId = isset($_POST['activation_id']) ? (int) $_POST['activation_id'] : 0;
                    $result = $licenseService->revokeActivation($activationId);
                }
                $_SESSION['license_settings_feedback'] = $result;
                $redirectParams['licenses_open'] = 1;
                if (isset($_POST['license_focus_id'])) {
                    $redirectParams['license_id'] = (int) $_POST['license_focus_id'];
                }
            } elseif ($action === 'save_pda_ocr') {
                $isPdaAction = true;
                if (!$isAdmin) {
                    $result = [
                        'success' => false,
                        'message' => 'Operazione non autorizzata.',
                        'errors' => ['Solo gli amministratori possono configurare il PDA.'],
                    ];
                } else {
                    $result = $pdaSettingsService->saveOcrSettings($_POST);
                }
                $_SESSION['pda_settings_feedback'] = $result;
                $redirectParams['pda_open'] = 1;
            } elseif ($action === 'save_pda_templates') {
                $isPdaAction = true;
                if (!$isAdmin) {
                    $result = [
                        'success' => false,
                        'message' => 'Operazione non autorizzata.',
                        'errors' => ['Solo gli amministratori possono configurare il PDA.'],
                    ];
                } else {
                    $result = $pdaSettingsService->saveTemplatesJson((string) ($_POST['pda_templates_json'] ?? ''));
                }
                $_SESSION['pda_settings_feedback'] = $result;
                $redirectParams['pda_open'] = 1;
            } elseif ($action === 'save_receipt_settings') {
                $isReceiptAction = true;
                if (!$canManageTenantSettings) {
                    $result = [
                        'success' => false,
                        'message' => 'Operazione non autorizzata.',
                        'errors' => ['Solo i responsabili del tenant possono configurare lo scontrino.'],
                    ];
                } else {
                    $result = $receiptSettingsService->saveSettings($_POST);
                }
                $_SESSION['receipt_settings_feedback'] = $result;
                $redirectParams['receipt_open'] = 1;
            } elseif ($action === 'update_product_tax') {
                if (!$canManageTenantSettings) {
                    $result = [
                        'success' => false,
                        'message' => 'Operazione non autorizzata.',
                        'error' => 'Solo i responsabili del tenant possono aggiornare le impostazioni fiscali.',
                    ];
                } else {
                    $productId = isset($_POST['product_id']) ? (int) $_POST['product_id'] : 0;
                    $taxRateInput = isset($_POST['product_tax_rate']) ? (float) $_POST['product_tax_rate'] : 0.0;
                    $vatCodeInput = array_key_exists('product_vat_code', $_POST)
                        ? (string) $_POST['product_vat_code']
                        : null;
                    $result = $productService->updateTaxSettings($productId, $taxRateInput, $vatCodeInput);
                }
                $redirectParams['fiscal_open'] = 1;
            } elseif ($action === 'create_discount_campaign') {
                if (!$canManageTenantSettings) {
                    $result = [
                        'success' => false,
                        'message' => 'Operazione non autorizzata.',
                        'error' => 'Solo i responsabili del tenant possono creare campagne.',
                    ];
                } else {
                    $result = $discountCampaignService->create($_POST);
                }
            } elseif ($action === 'toggle_discount_campaign') {
                if (!$canManageTenantSettings) {
                    $result = [
                        'success' => false,
                        'message' => 'Operazione non autorizzata.',
                        'error' => 'Solo i responsabili del tenant possono aggiornare campagne.',
                    ];
                } else {
                    $campaignId = (int) ($_POST['campaign_id'] ?? 0);
                    $target = isset($_POST['target_status']) ? ((int) $_POST['target_status'] === 1) : true;
                    $result = $discountCampaignService->setStatus($campaignId, $target);
                }
            } elseif ($action === 'force_disable_mfa') {
                if (!$canManageTenantSettings) {
                    $result = [
                        'success' => false,
                        'message' => 'Operazione non autorizzata.',
                        'error' => 'Solo i responsabili del tenant possono intervenire sull’MFA degli operatori.',
                    ];
                } else {
                    $operatorId = isset($_POST['operator_id']) ? (int) $_POST['operator_id'] : 0;
                    if (!$isAdmin && $operatorId > 0) {
                        $operatorRow = $userService->findUser($operatorId);
                        $operatorTenantId = isset($operatorRow['tenant_id']) ? (int) $operatorRow['tenant_id'] : 0;
                        if ($operatorRow === null || $operatorTenantId !== $tenantId) {
                            $result = [
                                'success' => false,
                                'message' => 'Operazione non autorizzata.',
                                'error' => 'Puoi intervenire solo su operatori del tuo tenant.',
                            ];
                            $redirectParams['operators_open'] = 1;
                            $_SESSION['settings_feedback'] = $result;
                            header('Location: index.php?' . http_build_query(['page' => 'settings', 'operators_open' => 1]));
                            exit;
                        }
                    }
                    $result = $authController->disableMfa($operatorId, null, true);
                    $result['message'] = ($result['success'] ?? false)
                        ? 'MFA disattivata per l’operatore selezionato.'
                        : ($result['error'] ?? 'Impossibile disattivare l’MFA per l’operatore.');
                }
                $redirectParams['operators_open'] = 1;
            } elseif ($action === 'sso_create_client') {
                $redirectParams['sso_open'] = 1;
                if (!$isAdmin) {
                    $result = [
                        'success' => false,
                        'message' => 'Operazione non autorizzata.',
                        'error' => 'Solo gli amministratori possono creare client SSO.',
                    ];
                } elseif (!$ssoEnabled) {
                    $result = [
                        'success' => false,
                        'message' => 'SSO non configurato.',
                        'error' => 'Configura SSO_SHARED_SECRET per abilitare l\'SSO interno.',
                    ];
                } else {
                    $clientName = trim((string) ($_POST['sso_client_name'] ?? ''));
                    $clientRedirect = trim((string) ($_POST['sso_redirect_uri'] ?? ''));
                    $creation = $ssoService->createClient($clientName, $clientRedirect, true);
                    $result = $creation;
                    if (($creation['success'] ?? false) && isset($creation['client_secret'])) {
                        $_SESSION['settings_sso_secret'] = [
                            'client_id' => $creation['client_id'] ?? '',
                            'client_secret' => $creation['client_secret'],
                        ];
                        $result['message'] = 'Client SSO creato. Annota il secret generato: sarà mostrato una sola volta.';
                    }
                }
                $_SESSION['settings_sso_feedback'] = $result;
            } elseif ($action === 'sso_rotate_client_secret') {
                $redirectParams['sso_open'] = 1;
                if (!$isAdmin) {
                    $result = [
                        'success' => false,
                        'message' => 'Operazione non autorizzata.',
                        'error' => 'Solo gli amministratori possono rigenerare i secret SSO.',
                    ];
                } elseif (!$ssoEnabled) {
                    $result = [
                        'success' => false,
                        'message' => 'SSO non configurato.',
                        'error' => 'Configura SSO_SHARED_SECRET per usare il single sign-on interno.',
                    ];
                } else {
                    $clientRowId = isset($_POST['client_id']) ? (int) $_POST['client_id'] : 0;
                    if ($clientRowId <= 0) {
                        $result = [
                            'success' => false,
                            'message' => 'Client SSO non valido.',
                        ];
                    } else {
                        $label = trim((string) ($_POST['client_label'] ?? ''));
                        $identifier = trim((string) ($_POST['client_identifier'] ?? ''));
                        $rotation = $ssoService->rotateClientSecret($clientRowId);
                        $result = $rotation;
                        if (($rotation['success'] ?? false) && isset($rotation['client_secret'])) {
                            $_SESSION['settings_sso_secret'] = [
                                'client_id' => $identifier,
                                'client_secret' => $rotation['client_secret'],
                            ];
                            $name = $label !== '' ? $label : $identifier;
                            $result['message'] = 'Secret rigenerato per il client ' . ($name !== '' ? $name : 'selezionato') . '. Annota il nuovo valore.';
                        }
                    }
                }
                $_SESSION['settings_sso_feedback'] = $result;
            } elseif ($action === 'sso_toggle_client') {
                $redirectParams['sso_open'] = 1;
                if (!$isAdmin) {
                    $result = [
                        'success' => false,
                        'message' => 'Operazione non autorizzata.',
                        'error' => 'Solo gli amministratori possono modificare lo stato dei client SSO.',
                    ];
                } elseif (!$ssoEnabled) {
                    $result = [
                        'success' => false,
                        'message' => 'SSO non configurato.',
                        'error' => 'Configura SSO_SHARED_SECRET per usare il single sign-on interno.',
                    ];
                } else {
                    $clientRowId = isset($_POST['client_id']) ? (int) $_POST['client_id'] : 0;
                    $targetStatus = isset($_POST['target_status']) ? ((int) $_POST['target_status'] === 1) : false;
                    $toggle = $ssoService->setClientStatus($clientRowId, $targetStatus);
                    $result = $toggle;
                }
                $_SESSION['settings_sso_feedback'] = $result;
            } elseif ($action === 'sso_delete_client') {
                $redirectParams['sso_open'] = 1;
                if (!$isAdmin) {
                    $result = [
                        'success' => false,
                        'message' => 'Operazione non autorizzata.',
                        'error' => 'Solo gli amministratori possono eliminare client SSO.',
                    ];
                } elseif (!$ssoEnabled) {
                    $result = [
                        'success' => false,
                        'message' => 'SSO non configurato.',
                        'error' => 'Configura SSO_SHARED_SECRET per usare il single sign-on interno.',
                    ];
                } else {
                    $clientRowId = isset($_POST['client_id']) ? (int) $_POST['client_id'] : 0;
                    $result = $ssoService->deleteClient($clientRowId);
                }
                $_SESSION['settings_sso_feedback'] = $result;
            } else {
                $result = [
                    'success' => false,
                    'message' => 'Azione non riconosciuta.',
                ];
            }

            if (!$isPdaAction && !$isReceiptAction && !$isEnergyAction && !$isLicenseAction) {
                $_SESSION['settings_feedback'] = $result;
            }

            $query = ['page' => 'settings'];
            foreach ($redirectParams as $key => $value) {
                if ($value === null) {
                    continue;
                }
                $query[$key] = $value;
            }

            header('Location: index.php?' . http_build_query($query));
            exit;
        }

        if ($canManageTenantSettings && $operatorEditId > 0) {
            $operatorEdit = $userService->findUser($operatorEditId);
            if (!$isAdmin && $operatorEdit !== null) {
                $operatorTenantId = isset($operatorEdit['tenant_id']) ? (int) $operatorEdit['tenant_id'] : 0;
                if ($operatorTenantId !== $tenantId) {
                    $operatorEdit = null;
                }
            }
            if ($operatorEdit === null) {
                if ($feedback === null) {
                    $feedback = [
                        'success' => false,
                        'message' => 'Operatore non trovato.',
                        'error' => 'Seleziona un operatore valido da modificare.',
                    ];
                }
                $operatorEditId = 0;
                $operatorEditForm = null;
                $operatorsOpenOverride = true;
            }
        }

    $auditPage = isset($_GET['audit_page']) ? max((int) $_GET['audit_page'], 1) : 1;
        $auditPerPage = isset($_GET['audit_per_page']) ? max(5, min((int) $_GET['audit_per_page'], 25)) : 10;
    $auditLogsResult = paginateAuditLogs($pdo, $auditPage, $auditPerPage, $isAdmin ? null : $tenantId);
        $buildAuditPageUrl = static function (int $pageNo) use ($auditLogsResult): string {
            $target = max(1, $pageNo);
            $perPage = (int) ($auditLogsResult['pagination']['per_page'] ?? 10);
            $params = [
                'page' => 'settings',
                'audit_page' => $target,
            ];
            if ($perPage !== 10) {
                $params['audit_per_page'] = $perPage;
            }

            return 'index.php?' . http_build_query($params);
        };
        $auditOpen = isset($_GET['audit_page']) || isset($_GET['audit_per_page']) || isset($_GET['audit_open']);

        render('settings', [
            'providerInsights' => $stockMonitorService->getProviderInsights(),
            'stockAlerts' => $stockMonitorService->getOpenAlerts(),
            'feedback' => $feedback,
            'currentUser' => $currentUser,
            'pageTitle' => 'Impostazioni',
            'roles' => $canManageTenantSettings
                ? ($isAdmin ? $userService->getRoles() : $userService->getRolesForTenant())
                : [],
            'operators' => $canManageTenantSettings
                ? ($isAdmin ? $userService->listOperators() : $userService->listOperators($tenantId))
                : [],
            'tenants' => $tenantsList,
            'licenses' => $licenses,
            'providers' => $canManageTenantSettings ? $providerService->listProviders() : [],
            'energyProviders' => $energyProviders,
            'energyOffersImportStatus' => $energyOffersImportStatus,
            'energyOpen' => $energyOpen,
            'energyFeedback' => $energySettingsFeedback,
            'licenseFeedback' => $licenseSettingsFeedback,
            'licenseGeneratedCode' => $licenseGeneratedCode,
            'licensesOpen' => $licensesOpen,
            'licenseActivations' => $licenseActivations,
            'licenseFocusId' => $licenseFocusId,
            'operatorEdit' => $operatorEdit,
            'operatorEditForm' => $operatorEditForm,
            'operatorsOpen' => $operatorsOpenOverride,
            'providersOpen' => $providersOpenOverride,
            'fiscalProducts' => $canManageTenantSettings ? $productService->listForFiscalSettings() : [],
            'fiscalOpen' => $fiscalOpen,
            'discountCampaigns' => $canManageTenantSettings ? $discountCampaignService->listAll() : [],
            'isAdmin' => $isAdmin,
            'canManageTenantSettings' => $canManageTenantSettings,
            'currentTenantId' => $tenantId,
            'auditLogs' => $auditLogsResult['rows'],
            'auditPagination' => $auditLogsResult['pagination'],
            'buildAuditPageUrl' => $buildAuditPageUrl,
            'auditOpen' => $auditOpen,
            'ssoEnabled' => $ssoEnabled,
            'ssoClients' => $ssoClients,
            'ssoFeedback' => $ssoFeedback,
            'ssoSecretPreview' => $ssoSecretPreview,
            'ssoOpen' => $ssoOpen,
            'ssoTokenTtl' => $ssoService->getTokenTtl(),
            'pdaSettings' => $pdaSettings,
            'pdaFeedback' => $pdaSettingsFeedback,
            'pdaOpen' => $pdaOpen,
            'receiptSettings' => $receiptSettings,
            'receiptFeedback' => $receiptSettingsFeedback,
            'receiptOpen' => $receiptOpen,
        ]);
        break;

    case 'pda_imports':
        if (!$authService->hasRole('admin')) {
            header('Location: index.php?page=dashboard');
            exit;
        }

        $pdaFeedback = $_SESSION['pda_imports_feedback'] ?? null;
        unset($_SESSION['pda_imports_feedback']);

        if ($method === 'POST' && ($_POST['action'] ?? '') === 'reprocess_pda') {
            $importId = isset($_POST['pda_import_id']) ? (int) $_POST['pda_import_id'] : 0;
            $result = $pdaImportController->reprocess($importId, $currentUser);
            $_SESSION['pda_imports_feedback'] = $result;
            header('Location: index.php?page=pda_imports&detail=' . $importId);
            exit;
        }

        $pageNo = isset($_GET['page_no']) ? max((int) $_GET['page_no'], 1) : 1;
        $perPage = 10;
        $importsList = $pdaImportController->list($pageNo, $perPage);
        $detail = null;
        if (isset($_GET['detail'])) {
            $detailId = max((int) $_GET['detail'], 0);
            if ($detailId > 0) {
                $detail = $pdaImportController->detail($detailId);
            }
        }

        render('pda_imports', [
            'imports' => $importsList['rows'],
            'pagination' => $importsList['pagination'],
            'detail' => $detail,
            'feedback' => $pdaFeedback,
            'currentUser' => $currentUser,
            'pageTitle' => 'Debug Import PDA',
        ]);
        break;

    case 'pda_settings':
        if (!$authService->hasRole('admin')) {
            header('Location: index.php?page=dashboard');
            exit;
        }
        if ($method === 'POST') {
            $action = $_POST['action'] ?? '';
            if ($action === 'save_pda_ocr') {
                $result = $pdaSettingsService->saveOcrSettings($_POST);
            } elseif ($action === 'save_pda_templates') {
                $result = $pdaSettingsService->saveTemplatesJson((string) ($_POST['pda_templates_json'] ?? ''));
            } else {
                $result = [
                    'success' => false,
                    'message' => 'Azione non riconosciuta.',
                    'errors' => ['Richiesta non valida.'],
                ];
            }

            $_SESSION['pda_settings_feedback'] = $result;
            header('Location: index.php?page=settings&pda_open=1');
            exit;
        }

        header('Location: index.php?page=settings&pda_open=1');
        exit;

    case 'security':
        $userId = isset($currentUser['id']) ? (int) $currentUser['id'] : 0;
        if ($userId <= 0) {
            header('Location: index.php?page=login');
            exit;
        }

        $issuer = $GLOBALS['config']['app']['name'] ?? 'Gestionale Telefonia';
        $securityFeedback = $_SESSION['security_feedback'] ?? null;
        unset($_SESSION['security_feedback']);
        $securityCodes = $_SESSION['security_recovery_codes'] ?? [];
        unset($_SESSION['security_recovery_codes']);

        if ($method === 'POST') {
            $action = isset($_POST['action']) ? (string) $_POST['action'] : '';
            $redirectParams = [];
            $message = null;
            $redirectPage = 'security';

            if ($action === 'start_setup') {
                $setupResult = $authController->beginMfaSetup($userId, $issuer);
                if ($setupResult['success'] ?? false) {
                    $message = [
                        'success' => true,
                        'message' => 'Scansiona il QR code e conferma il codice di verifica.',
                    ];
                    $redirectParams['setup'] = 1;
                } else {
                    $message = [
                        'success' => false,
                        'message' => $setupResult['error'] ?? 'Impossibile avviare la configurazione MFA.',
                    ];
                }
            } elseif ($action === 'cancel_setup') {
                $authController->cancelMfaSetup($userId);
                $message = [
                    'success' => true,
                    'message' => 'Configurazione MFA annullata.',
                ];
            } elseif ($action === 'confirm_setup') {
                $code = isset($_POST['mfa_code']) ? (string) $_POST['mfa_code'] : '';
                $setupResult = $authController->confirmMfaSetup($userId, $code);
                if ($setupResult['success'] ?? false) {
                    $_SESSION['security_recovery_codes'] = $setupResult['recovery_codes'] ?? [];
                    $message = [
                        'success' => true,
                        'message' => 'Autenticazione a due fattori attivata correttamente.',
                    ];
                    if (!$receiptSettingsService->isConfigured()) {
                        $redirectPage = 'settings';
                        $redirectParams['receipt_open'] = 1;
                        pushFlashToast([
                            'type' => 'info',
                            'title' => 'Configura lo scontrino',
                            'message' => 'Completa la configurazione iniziale impostando le diciture dello scontrino.',
                            'duration' => 0,
                            'dismissible' => false,
                        ]);
                    }
                } else {
                    $message = [
                        'success' => false,
                        'message' => $setupResult['error'] ?? 'Impossibile confermare il codice MFA.',
                    ];
                    $redirectParams['setup'] = 1;
                }
            } elseif ($action === 'disable_mfa') {
                $code = isset($_POST['mfa_code']) ? (string) $_POST['mfa_code'] : '';
                $disableResult = $authController->disableMfa($userId, $code, false);
                $message = [
                    'success' => $disableResult['success'] ?? false,
                    'message' => $disableResult['message'] ?? ($disableResult['error'] ?? 'Operazione completata.'),
                ];
            } elseif ($action === 'regenerate_codes') {
                $code = isset($_POST['mfa_code']) ? (string) $_POST['mfa_code'] : '';
                $regenResult = $authController->regenerateRecoveryCodes($userId, $code);
                if ($regenResult['success'] ?? false) {
                    $_SESSION['security_recovery_codes'] = $regenResult['recovery_codes'] ?? [];
                    $message = [
                        'success' => true,
                        'message' => 'Nuovi codici di recupero generati.',
                    ];
                } else {
                    $message = [
                        'success' => false,
                        'message' => $regenResult['error'] ?? 'Impossibile rigenerare i codici di recupero.',
                    ];
                }
            }

            if ($message !== null) {
                $_SESSION['security_feedback'] = $message;
            }

            $query = ['page' => $redirectPage];
            foreach ($redirectParams as $key => $value) {
                if ($value === null) {
                    continue;
                }
                $query[$key] = $value;
            }

            header('Location: index.php?' . http_build_query($query));
            exit;
        }

        $state = $authController->getSecurityState($userId);
        if ($state === null) {
            $state = ['mfa_enabled' => false, 'mfa_enabled_at' => null];
        }

        $setupData = null;
        if (isset($_GET['setup'])) {
            $setupResult = $authController->getMfaSetupSecret($userId, $issuer);
            if ($setupResult['success'] ?? false) {
                $setupData = $setupResult;
            } else {
                if ($securityFeedback === null) {
                    $securityFeedback = [
                        'success' => false,
                        'message' => $setupResult['error'] ?? 'Impossibile recuperare i dati di configurazione.',
                    ];
                }
            }
        }

        render('security', [
            'currentUser' => $currentUser,
            'pageTitle' => 'Sicurezza account',
            'state' => $state,
            'setupData' => $setupData,
            'feedback' => $securityFeedback,
            'recoveryCodes' => $securityCodes,
            'issuer' => $issuer,
        ]);
        break;

    case 'iccid_list':
        $iccidPage = isset($_GET['page_no']) ? max((int) $_GET['page_no'], 1) : 1;
        $iccidPerPage = 7;
        if (isset($_GET['per_page'])) {
            $requested = max(1, min((int) $_GET['per_page'], 50));
            if ($requested !== $iccidPerPage) {
                $iccidPerPage = $requested;
            }
        }
        $iccidList = $iccidController->listPaginated($iccidPage, $iccidPerPage);

        if ($method === 'GET' && (($_GET['action'] ?? '') === 'refresh' || isAjaxRequest())) {
            jsonResponse([
                'success' => true,
                'payload' => [
                    'rows' => $iccidList['rows'],
                    'pagination' => $iccidList['pagination'],
                ],
            ]);
        }

        render('iccid_list', [
            'stock' => $iccidList['rows'],
            'pagination' => $iccidList['pagination'],
            'currentUser' => $currentUser,
        ]);
        break;

    case 'sales_create':
        $feedbackCreate = $_SESSION['sale_create_feedback'] ?? null;
        $feedbackCancel = $_SESSION['sale_cancel_feedback'] ?? null;
        $feedbackRefund = $_SESSION['sale_refund_feedback'] ?? null;
        $pdaFeedback = $_SESSION['sale_pda_feedback'] ?? null;
        $pdaPrefill = $_SESSION['sale_pda_prefill'] ?? null;
        $pdaPreview = $_SESSION['sale_pda_preview'] ?? null;
        $pdaImportId = $_SESSION['sale_pda_import_id'] ?? null;
        unset(
            $_SESSION['sale_create_feedback'],
            $_SESSION['sale_cancel_feedback'],
            $_SESSION['sale_refund_feedback'],
            $_SESSION['sale_pda_feedback'],
            $_SESSION['sale_pda_prefill'],
            $_SESSION['sale_pda_preview'],
            $_SESSION['sale_pda_import_id']
        );

        if ($method === 'POST' && ($_POST['action'] ?? '') === 'load_sale_details') {
            $saleId = isset($_POST['sale_id']) ? (int) $_POST['sale_id'] : 0;
            $payload = $salesController->loadSaleForRefund($saleId);
            header('Content-Type: application/json; charset=utf-8');
            echo json_encode($payload);
            exit;
        }

        if ($method === 'POST') {
            $action = $_POST['action'] ?? 'create_sale';
            if ($action === 'upload_pda') {
                $result = $pdaImportController->upload($_FILES, $_POST, $currentUser);
                $_SESSION['sale_pda_feedback'] = $result;
                if (($result['success'] ?? false) && isset($result['preview'], $result['import_id'])) {
                    $_SESSION['sale_pda_preview'] = $result['preview'];
                    $_SESSION['sale_pda_import_id'] = $result['import_id'];
                }
                header('Location: index.php?page=sales_create');
                exit;
            }
            if ($action === 'confirm_pda_import') {
                $result = $pdaImportController->confirm($_POST, $currentUser);
                $_SESSION['sale_pda_feedback'] = $result;
                if (($result['success'] ?? false) && isset($result['prefill'])) {
                    $_SESSION['sale_pda_prefill'] = $result['prefill'];
                } else {
                    $_SESSION['sale_pda_preview'] = $pdaPreview ?? null;
                    $_SESSION['sale_pda_import_id'] = $pdaImportId ?? null;
                }
                header('Location: index.php?page=sales_create');
                exit;
            }
            if ($action === 'cancel_pda_import') {
                $result = $pdaImportController->cancel($_POST, $currentUser);
                $_SESSION['sale_pda_feedback'] = $result;
                header('Location: index.php?page=sales_create');
                exit;
            }
            if ($action === 'cancel_sale') {
                $result = $salesController->cancel($currentUser['id'], $_POST);
                $_SESSION['sale_cancel_feedback'] = $result;
                header('Location: index.php?page=sales_create');
                exit;
            }
            if ($action === 'refund_sale') {
                $result = $salesController->refund($currentUser['id'], $_POST);
                $_SESSION['sale_refund_feedback'] = $result;
                header('Location: index.php?page=sales_create');
                exit;
            }

            $feedback = $salesController->create($currentUser['id'], $_POST);
            if (($feedback['success'] ?? false) === true) {
                $_SESSION['sale_create_feedback'] = $feedback;
                header('Location: index.php?page=sales_create&print=' . (int) $feedback['sale_id']);
                exit;
            }

            $_SESSION['sale_create_feedback'] = $feedback;
            header('Location: index.php?page=sales_create');
            exit;
        }

        $availableProvidersRaw = $iccidController->providers();
        $availableProviders = array_values(array_filter(
            $availableProvidersRaw,
            static fn (array $provider): bool => strcasecmp((string) ($provider['name'] ?? ''), 'iliad') !== 0
        ));

        render('sales_create', [
            'availableIccid' => $iccidController->available(),
            'availableOffers' => $offersController->listActive(),
            'availableProducts' => $productController->listActive(),
            'availableCustomers' => $customerController->list(),
            'discountCampaigns' => $discountCampaignService->listActive(),
            'feedbackCreate' => $feedbackCreate,
            'feedbackCancel' => $feedbackCancel,
            'feedbackRefund' => $feedbackRefund,
            'pdaFeedback' => $pdaFeedback,
            'pdaPrefill' => $pdaPrefill,
            'availableProviders' => $availableProviders,
            'currentUser' => $currentUser,
        ]);
        break;

    case 'sales_list':
        $filters = [
            'q' => $_GET['q'] ?? null,
            'status' => $_GET['status'] ?? null,
            'from' => $_GET['from'] ?? null,
            'to' => $_GET['to'] ?? null,
            'payment' => $_GET['payment'] ?? null,
        ];
        $pageNumber = isset($_GET['page_no']) ? max((int) $_GET['page_no'], 1) : 1;
        $perPage = 7;
        if (isset($_GET['per_page'])) {
            $requested = max(1, min((int) $_GET['per_page'], 50));
            if ($requested !== $perPage) {
                $perPage = $requested;
            }
        }

        $list = $salesController->listSales($filters, $pageNumber, $perPage);

        if ($method === 'GET' && (($_GET['action'] ?? '') === 'refresh' || isAjaxRequest())) {
            jsonResponse([
                'success' => true,
                'payload' => [
                    'rows' => formatSalesRowsForJson($list['rows']),
                    'pagination' => $list['pagination'],
                    'filters' => $filters,
                ],
            ]);
        }

        render('sales_list', [
            'sales' => $list['rows'],
            'filters' => $filters,
            'pagination' => $list['pagination'],
            'currentUser' => $currentUser,
            'pageTitle' => 'Storico vendite',
        ]);
        break;

    case 'energy_contracts':
        $energyFeedback = $_SESSION['energy_contracts_feedback'] ?? null;
        unset($_SESSION['energy_contracts_feedback']);

        if ($method === 'POST') {
            $action = $_POST['action'] ?? '';
            if ($action === 'energy_bill_parse') {
                $file = $_FILES['bill_file'] ?? null;
                if (!is_array($file) || ($file['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK) {
                    jsonResponse(['success' => false, 'message' => 'Carica un file PDF valido.'], 422);
                }

                $maxSize = 8 * 1024 * 1024;
                if (($file['size'] ?? 0) > $maxSize) {
                    jsonResponse(['success' => false, 'message' => 'Il file supera 8MB.'], 422);
                }

                $ext = strtolower(pathinfo((string) ($file['name'] ?? ''), PATHINFO_EXTENSION));
                if ($ext !== 'pdf') {
                    jsonResponse(['success' => false, 'message' => 'Formato non supportato. Usa PDF.'], 422);
                }

                try {
                    $data = parseEnergyBillPdf((string) ($file['tmp_name'] ?? ''));
                    jsonResponse([
                        'success' => true,
                        'data' => $data,
                        'message' => 'Bolletta analizzata.',
                    ]);
                } catch (\Throwable $exception) {
                    jsonResponse([
                        'success' => false,
                        'message' => 'Impossibile leggere la bolletta.',
                        'error' => $exception->getMessage(),
                    ], 500);
                }
            } elseif ($action === 'create_energy_contract') {
                $userId = isset($currentUser['id']) ? (int) $currentUser['id'] : null;
                $result = $energyContractController->create($_POST, $userId);
                $_SESSION['energy_contracts_feedback'] = $result;
            } elseif ($action === 'delete_energy_contract') {
                $contractId = isset($_POST['contract_id']) ? (int) $_POST['contract_id'] : 0;
                $result = $energyContractController->delete($contractId);
                $_SESSION['energy_contracts_feedback'] = $result;
            } elseif ($action === 'energy_sim_request') {
                $name = trim((string) ($_POST['contact_name'] ?? ''));
                $email = trim((string) ($_POST['contact_email'] ?? ''));
                $phone = trim((string) ($_POST['contact_phone'] ?? ''));
                $preferred = trim((string) ($_POST['preferred_time'] ?? ''));
                $note = trim((string) ($_POST['contact_note'] ?? ''));
                $requestType = trim((string) ($_POST['request_type'] ?? 'richiesta'));
                $simPayload = trim((string) ($_POST['sim_payload'] ?? ''));
                $simSummary = trim((string) ($_POST['sim_summary'] ?? ''));

                $errors = [];
                if ($name === '') {
                    $errors[] = 'Inserisci il nome del contatto.';
                }
                if ($email === '' && $phone === '') {
                    $errors[] = 'Inserisci almeno email o telefono.';
                }

                if ($errors !== []) {
                    $_SESSION['energy_contracts_feedback'] = [
                        'success' => false,
                        'message' => 'Impossibile inviare la richiesta.',
                        'errors' => $errors,
                    ];
                } else {
                    $payloadJson = null;
                    if ($simPayload !== '') {
                        $decoded = json_decode($simPayload, true);
                        if (is_array($decoded)) {
                            $payloadJson = json_encode($decoded, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
                        }
                    }

                    try {
                        $stmt = $pdo->prepare(
                            'INSERT INTO energy_sim_requests
                                (request_type, contact_name, contact_email, contact_phone, preferred_time, contact_note, sim_payload, sim_summary, user_id)
                             VALUES
                                (:request_type, :contact_name, :contact_email, :contact_phone, :preferred_time, :contact_note, :sim_payload, :sim_summary, :user_id)'
                        );
                        $stmt->execute([
                            ':request_type' => $requestType !== '' ? $requestType : 'richiesta',
                            ':contact_name' => $name,
                            ':contact_email' => $email !== '' ? $email : null,
                            ':contact_phone' => $phone !== '' ? $phone : null,
                            ':preferred_time' => $preferred !== '' ? $preferred : null,
                            ':contact_note' => $note !== '' ? $note : null,
                            ':sim_payload' => $payloadJson,
                            ':sim_summary' => $simSummary !== '' ? $simSummary : null,
                            ':user_id' => isset($currentUser['id']) ? (int) $currentUser['id'] : null,
                        ]);

                        $_SESSION['energy_contracts_feedback'] = [
                            'success' => true,
                            'message' => 'Richiesta salvata correttamente. Ti ricontatteremo a breve.',
                        ];
                    } catch (\Throwable $exception) {
                        $_SESSION['energy_contracts_feedback'] = [
                            'success' => false,
                            'message' => 'Impossibile salvare la richiesta.',
                            'errors' => [$exception->getMessage()],
                        ];
                    }
                }
            } elseif ($action === 'energy_sim_upload') {
                $name = trim((string) ($_POST['contact_name'] ?? ''));
                $email = trim((string) ($_POST['contact_email'] ?? ''));
                $phone = trim((string) ($_POST['contact_phone'] ?? ''));
                $note = trim((string) ($_POST['contact_note'] ?? ''));
                $simPayload = trim((string) ($_POST['sim_payload'] ?? ''));
                $simSummary = trim((string) ($_POST['sim_summary'] ?? ''));

                $errors = [];
                if ($name === '') {
                    $errors[] = 'Inserisci il nome del contatto.';
                }
                if ($email === '' && $phone === '') {
                    $errors[] = 'Inserisci almeno email o telefono.';
                }

                $file = $_FILES['bill_file'] ?? null;
                if (!is_array($file) || ($file['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK) {
                    $errors[] = 'Carica un file valido della bolletta.';
                }

                $storedPath = null;
                if ($errors === [] && is_array($file)) {
                    $maxSize = 6 * 1024 * 1024;
                    if (($file['size'] ?? 0) > $maxSize) {
                        $errors[] = 'Il file supera la dimensione massima di 6MB.';
                    }

                    $ext = strtolower(pathinfo((string) ($file['name'] ?? ''), PATHINFO_EXTENSION));
                    $allowed = ['pdf', 'jpg', 'jpeg', 'png'];
                    if (!in_array($ext, $allowed, true)) {
                        $errors[] = 'Formato file non supportato.';
                    }

                    if ($errors === []) {
                        $relativeDir = 'uploads/energy_bills/' . date('Ym');
                        $uploadDir = __DIR__ . '/' . $relativeDir;
                        if (!is_dir($uploadDir)) {
                            mkdir($uploadDir, 0775, true);
                        }
                        $safeName = preg_replace('/[^a-z0-9\._-]+/i', '_', (string) ($file['name'] ?? 'bolletta'));
                        $filename = time() . '-' . bin2hex(random_bytes(4)) . '-' . $safeName;
                        $target = $uploadDir . '/' . $filename;
                        if (move_uploaded_file((string) ($file['tmp_name'] ?? ''), $target)) {
                            $storedPath = $relativeDir . '/' . $filename;
                        } else {
                            $errors[] = 'Impossibile salvare il file caricato.';
                        }
                    }
                }

                if ($errors !== []) {
                    $_SESSION['energy_contracts_feedback'] = [
                        'success' => false,
                        'message' => 'Upload non completato.',
                        'errors' => $errors,
                    ];
                } else {
                    $payloadJson = null;
                    if ($simPayload !== '') {
                        $decoded = json_decode($simPayload, true);
                        if (is_array($decoded)) {
                            $payloadJson = json_encode($decoded, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
                        }
                    }

                    try {
                        $stmt = $pdo->prepare(
                            'INSERT INTO energy_sim_requests
                                (request_type, contact_name, contact_email, contact_phone, contact_note, sim_payload, sim_summary, bill_file_path, user_id)
                             VALUES
                                (:request_type, :contact_name, :contact_email, :contact_phone, :contact_note, :sim_payload, :sim_summary, :bill_file_path, :user_id)'
                        );
                        $stmt->execute([
                            ':request_type' => 'upload',
                            ':contact_name' => $name,
                            ':contact_email' => $email !== '' ? $email : null,
                            ':contact_phone' => $phone !== '' ? $phone : null,
                            ':contact_note' => $note !== '' ? $note : null,
                            ':sim_payload' => $payloadJson,
                            ':sim_summary' => $simSummary !== '' ? $simSummary : null,
                            ':bill_file_path' => $storedPath,
                            ':user_id' => isset($currentUser['id']) ? (int) $currentUser['id'] : null,
                        ]);

                        $_SESSION['energy_contracts_feedback'] = [
                            'success' => true,
                            'message' => 'Bolletta caricata correttamente. Ti ricontatteremo a breve.',
                        ];
                    } catch (\Throwable $exception) {
                        $_SESSION['energy_contracts_feedback'] = [
                            'success' => false,
                            'message' => 'Impossibile salvare la richiesta.',
                            'errors' => [$exception->getMessage()],
                        ];
                    }
                }
            }
            header('Location: index.php?page=energy_contracts');
            exit;
        }

        $period = isset($_GET['period']) ? (string) $_GET['period'] : 'month';
        $date = isset($_GET['date']) ? trim((string) $_GET['date']) : '';
        $focusContractId = isset($_GET['focus']) ? max((int) $_GET['focus'], 0) : null;
        $contractsData = $energyContractController->listByPeriod($period, $date !== '' ? $date : null);

        render('energy_contracts', [
            'energyProviders' => $energyProviderService->listProviders(),
            'energyOffers' => $energyOfferService->listOffersForSimulator(),
            'contractsData' => $contractsData,
            'feedback' => $energyFeedback,
            'period' => $period,
            'dateValue' => $date,
            'focusContractId' => $focusContractId,
            'currentUser' => $currentUser,
            'pageTitle' => 'Contratti energia',
        ]);
        break;

    case 'product_requests':
        $feedbackProductRequests = $_SESSION['product_requests_feedback'] ?? null;
        unset($_SESSION['product_requests_feedback']);
        $filters = [
            'status' => $_GET['status'] ?? null,
            'type' => $_GET['type'] ?? null,
            'payment' => $_GET['payment'] ?? null,
            'q' => $_GET['q'] ?? null,
            'from' => $_GET['from'] ?? null,
            'to' => $_GET['to'] ?? null,
        ];

        $pageNumber = isset($_GET['page_no']) ? max((int) $_GET['page_no'], 1) : 1;
        $perPage = 10;
        if (isset($_GET['per_page'])) {
            $requestedPerPage = max(1, min((int) $_GET['per_page'], 50));
            if ($requestedPerPage !== $perPage) {
                $perPage = $requestedPerPage;
            }
        }

        $requests = $productRequestController->list($filters, $pageNumber, $perPage);
        $summary = $productRequestController->summary();
        $statusOptions = $productRequestController->statusOptions();
        $typeOptions = $productRequestController->typeOptions();
        $paymentOptions = $productRequestController->paymentOptions();

        render('product_requests', [
            'requests' => $requests['rows'],
            'pagination' => $requests['pagination'],
            'filters' => $requests['filters'],
            'summary' => $summary,
            'statusOptions' => $statusOptions,
            'typeOptions' => $typeOptions,
            'paymentOptions' => $paymentOptions,
            'perPage' => $perPage,
            'feedback' => $feedbackProductRequests,
            'currentUser' => $currentUser,
            'pageTitle' => 'Richieste acquisto prodotti',
        ]);
        break;

    case 'product_request':
        if ($method === 'POST') {
            $requestId = isset($_POST['request_id']) ? max((int) $_POST['request_id'], 0) : 0;
            $result = $productRequestController->update($requestId, $_POST, $currentUser ?? []);
            $_SESSION['product_request_feedback'] = $result;
            if (($result['success'] ?? false) === true) {
                $_SESSION['product_requests_feedback'] = $result;
            }

            $backCandidate = isset($_POST['back']) ? (string) $_POST['back'] : '';
            $backUrl = sanitizeInternalUrl($backCandidate, 'index.php?page=product_requests');

            $redirect = 'index.php?page=product_request';
            if ($requestId > 0) {
                $redirect .= '&request_id=' . $requestId;
            }
            if ($backUrl !== 'index.php?page=product_requests') {
                $redirect .= '&back=' . rawurlencode($backUrl);
            }

            header('Location: ' . $redirect);
            exit;
        }

        $feedbackRequest = $_SESSION['product_request_feedback'] ?? null;
        unset($_SESSION['product_request_feedback']);

        $requestId = isset($_GET['request_id']) ? max((int) $_GET['request_id'], 0) : 0;
        if ($requestId <= 0) {
            $_SESSION['product_requests_feedback'] = [
                'success' => false,
                'message' => 'Richiesta non valida.',
                'errors' => ['Seleziona una richiesta esistente per continuare.'],
            ];
            header('Location: index.php?page=product_requests');
            exit;
        }

        $request = $productRequestController->get($requestId);
        if ($request === null) {
            $_SESSION['product_requests_feedback'] = [
                'success' => false,
                'message' => 'Richiesta non trovata.',
                'errors' => ['La richiesta indicata non è più disponibile.'],
            ];
            header('Location: index.php?page=product_requests');
            exit;
        }

        $backCandidate = isset($_GET['back']) ? (string) $_GET['back'] : '';
        $backUrl = sanitizeInternalUrl($backCandidate, 'index.php?page=product_requests');
        $backEncoded = $backUrl === 'index.php?page=product_requests'
            ? ''
            : rawurlencode($backUrl);

        render('product_request_detail', [
            'request' => $request,
            'statusOptions' => $productRequestController->statusOptions(),
            'typeOptions' => $productRequestController->typeOptions(),
            'paymentOptions' => $productRequestController->paymentOptions(),
            'feedback' => $feedbackRequest,
            'backUrl' => $backUrl,
            'backEncoded' => $backEncoded,
            'currentUser' => $currentUser,
            'pageTitle' => 'Richiesta acquisto #' . $requestId,
        ]);
        break;

    case 'support_requests':
        $feedbackSupport = $_SESSION['support_requests_feedback'] ?? null;
        unset($_SESSION['support_requests_feedback']);

        $filters = [
            'status' => $_GET['status'] ?? null,
            'type' => $_GET['type'] ?? null,
            'q' => $_GET['q'] ?? null,
            'from' => $_GET['from'] ?? null,
            'to' => $_GET['to'] ?? null,
        ];

        $pageNumber = isset($_GET['page_no']) ? max((int) $_GET['page_no'], 1) : 1;
        $perPage = 10;
        if (isset($_GET['per_page'])) {
            $requestedPerPage = max(1, min((int) $_GET['per_page'], 50));
            if ($requestedPerPage !== $perPage) {
                $perPage = $requestedPerPage;
            }
        }

        $requests = $supportRequestController->list($filters, $pageNumber, $perPage);
        $summary = $supportRequestController->statusSummary();
        $statusOptions = $supportRequestController->statusOptions();
        $typeOptions = $supportRequestController->typeOptions();

        render('support_requests', [
            'requests' => $requests['rows'],
            'pagination' => $requests['pagination'],
            'filters' => $requests['filters'],
            'summary' => $summary,
            'statusOptions' => $statusOptions,
            'typeOptions' => $typeOptions,
            'perPage' => $perPage,
            'feedback' => $feedbackSupport,
            'currentUser' => $currentUser,
            'pageTitle' => 'Richieste assistenza',
        ]);
        break;

    case 'support_request':
        if ($method === 'POST') {
            $result = $supportRequestController->update($_POST, $currentUser ?? []);
            $_SESSION['support_request_feedback'] = $result;
            if (($result['success'] ?? false) === true) {
                $_SESSION['support_requests_feedback'] = $result;
            }

            $targetId = isset($_POST['request_id']) ? max((int) $_POST['request_id'], 0) : 0;
            $backCandidate = isset($_POST['back']) ? (string) $_POST['back'] : '';
            $backUrl = sanitizeInternalUrl($backCandidate, 'index.php?page=support_requests');

            $redirect = 'index.php?page=support_request';
            if ($targetId > 0) {
                $redirect .= '&request_id=' . $targetId;
            }
            if ($backUrl !== 'index.php?page=support_requests') {
                $redirect .= '&back=' . rawurlencode($backUrl);
            }

            header('Location: ' . $redirect);
            exit;
        }

        $feedbackRequest = $_SESSION['support_request_feedback'] ?? null;
        unset($_SESSION['support_request_feedback']);

        $requestId = isset($_GET['request_id']) ? max((int) $_GET['request_id'], 0) : 0;
        if ($requestId <= 0) {
            $_SESSION['support_requests_feedback'] = [
                'success' => false,
                'message' => 'Richiesta non valida.',
                'errors' => ['Seleziona una richiesta esistente per continuare.'],
            ];
            header('Location: index.php?page=support_requests');
            exit;
        }

        $request = $supportRequestController->find($requestId);
        if ($request === null) {
            $_SESSION['support_requests_feedback'] = [
                'success' => false,
                'message' => 'Richiesta non trovata.',
                'errors' => ['La richiesta indicata non è più disponibile.'],
            ];
            header('Location: index.php?page=support_requests');
            exit;
        }

        $backCandidate = isset($_GET['back']) ? (string) $_GET['back'] : '';
        $backUrl = sanitizeInternalUrl($backCandidate, 'index.php?page=support_requests');
        $backEncoded = $backUrl === 'index.php?page=support_requests'
            ? ''
            : rawurlencode($backUrl);

        render('support_request_detail', [
            'request' => $request,
            'statusOptions' => $supportRequestController->statusOptions(),
            'feedback' => $feedbackRequest,
            'backUrl' => $backUrl,
            'backEncoded' => $backEncoded,
            'currentUser' => $currentUser,
            'pageTitle' => 'Richiesta assistenza #' . $requestId,
        ]);
        break;

    case 'offers':
        $feedback = $_SESSION['offers_feedback'] ?? null;
        unset($_SESSION['offers_feedback']);
        $offersPage = isset($_GET['page_no']) ? max((int) $_GET['page_no'], 1) : 1;
        $offersPerPage = 7;
        $offersSearch = isset($_GET['search']) ? trim((string) $_GET['search']) : '';

        if ($method === 'POST') {
            $action = $_POST['action'] ?? 'save';
            if ($action === 'toggle_status') {
                $offerId = (int) ($_POST['id'] ?? 0);
                $status = $_POST['status'] ?? 'Inactive';
                if ($offerId > 0) {
                    $offersController->setStatus($offerId, $status === 'Active' ? 'Active' : 'Inactive');
                    $_SESSION['offers_feedback'] = [
                        'success' => true,
                        'message' => 'Stato offerta aggiornato.',
                    ];
                }
            } else {
                $result = $offersController->save($_POST);
                if (($result['success'] ?? false) === true) {
                    $result['message'] = isset($_POST['id']) && $_POST['id'] !== ''
                        ? 'Offerta aggiornata.'
                        : 'Offerta creata.';
                }
                $_SESSION['offers_feedback'] = $result;
            }
            $redirectPage = isset($_POST['page_no']) ? max((int) $_POST['page_no'], 1) : $offersPage;
            $redirectSearch = isset($_POST['search_term']) ? trim((string) $_POST['search_term']) : $offersSearch;
            $redirectParams = ['page' => 'offers'];
            if ($redirectPage > 1) {
                $redirectParams['page_no'] = $redirectPage;
            }
            if ($redirectSearch !== '') {
                $redirectParams['search'] = $redirectSearch;
            }
            header('Location: index.php?' . http_build_query($redirectParams));
            exit;
        }

        $editId = isset($_GET['edit']) ? (int) $_GET['edit'] : null;
        $editOffer = null;
        if ($editId !== null && $editId > 0) {
            $editOffer = $offersController->find($editId);
        }
        $offersList = $offersController->listPaginated($offersPage, $offersPerPage, null, $offersSearch !== '' ? $offersSearch : null);

        render('offers', [
            'offers' => $offersList['rows'],
            'providers' => $offersController->providers(),
            'editOffer' => $editOffer,
            'feedback' => $feedback,
            'currentUser' => $currentUser,
            'pageTitle' => 'Listini & Canvass',
            'pagination' => $offersList['pagination'],
            'search' => $offersSearch,
        ]);
        break;

    case 'notifications':
        if (!isset($systemNotificationService)) {
            http_response_code(503);
            echo 'Servizio notifiche non disponibile';
            exit;
        }

        $pageNo = isset($_GET['page_no']) ? max((int) $_GET['page_no'], 1) : 1;
        $perPage = isset($_GET['per_page']) ? max(5, min((int) $_GET['per_page'], 50)) : 20;
        $focusNotificationId = isset($_GET['focus']) ? max(0, (int) $_GET['focus']) : null;

        $userId = null;
        if (is_array($currentUser) && isset($currentUser['id'])) {
            $userId = (int) $currentUser['id'];
        }

        $feed = $systemNotificationService->getPaginatedFeed($userId, $pageNo, $perPage);

        render('notifications', [
            'notifications' => $feed['items'],
            'pagination' => $feed['pagination'],
            'currentUser' => $currentUser,
            'focusNotificationId' => $focusNotificationId,
            'pageTitle' => 'Notifiche',
        ]);
        break;

    default:
        http_response_code(404);
        echo 'Pagina non trovata';
        break;

    // API endpoints
    case 'api/customers':
        $apiUser = authenticateApi();
        $currentUser = $apiUser ?? $currentUser;
        if ($currentUser === null) {
            http_response_code(401);
            jsonResponse(['error' => 'Unauthorized']);
            break;
        }

        $id = isset($_GET['id']) ? (int) $_GET['id'] : null;
        if ($method === 'GET') {
            if ($id !== null && $id > 0) {
                $customer = $customerController->find($id);
                if ($customer === null) {
                    jsonResponse(['error' => 'Customer not found'], 404);
                } else {
                    jsonResponse(['success' => true, 'data' => $customer]);
                }
            } else {
                $customers = $customerController->list();
                jsonResponse(['success' => true, 'data' => $customers]);
            }
        } elseif ($method === 'POST') {
            $input = getJsonBody();
            $result = $customerController->create($input);
            jsonResponse($result, $result['success'] ? 201 : 422);
        } elseif ($method === 'PUT') {
            if ($id === null || $id <= 0) {
                jsonResponse(['error' => 'ID required'], 400);
            } else {
                $input = getJsonBody();
                $result = $customerController->update($id, $input);
                jsonResponse($result, $result['success'] ? 200 : 422);
            }
        } elseif ($method === 'DELETE') {
            if ($id === null || $id <= 0) {
                jsonResponse(['error' => 'ID required'], 400);
            } else {
                $result = $customerController->delete($id);
                jsonResponse($result, $result['success'] ? 200 : 422);
            }
        } else {
            http_response_code(405);
            jsonResponse(['error' => 'Method not allowed']);
        }
        break;

    case 'api/products':
        $apiUser = authenticateApi();
        $currentUser = $apiUser ?? $currentUser;
        if ($currentUser === null) {
            http_response_code(401);
            jsonResponse(['error' => 'Unauthorized']);
            break;
        }

        $id = isset($_GET['id']) ? (int) $_GET['id'] : null;
        if ($method === 'GET') {
            if ($id !== null && $id > 0) {
                $product = $productController->find($id);
                if ($product === null) {
                    jsonResponse(['error' => 'Product not found'], 404);
                } else {
                    jsonResponse(['success' => true, 'data' => $product]);
                }
            } else {
                $products = $productController->listActive();
                jsonResponse(['success' => true, 'data' => $products]);
            }
        } elseif ($method === 'POST') {
            $input = getJsonBody();
            $result = $productController->create($input, $currentUser['id']);
            jsonResponse($result, $result['success'] ? 201 : 422);
        } elseif ($method === 'PUT') {
            if ($id === null || $id <= 0) {
                jsonResponse(['error' => 'ID required'], 400);
            } else {
                $input = getJsonBody();
                $result = $productController->update($id, $input, $currentUser['id']);
                jsonResponse($result, $result['success'] ? 200 : 422);
            }
        } elseif ($method === 'DELETE') {
            if ($id === null || $id <= 0) {
                jsonResponse(['error' => 'ID required'], 400);
            } else {
                $result = $productController->delete($id);
                jsonResponse($result, $result['success'] ? 200 : 422);
            }
        } else {
            http_response_code(405);
            jsonResponse(['error' => 'Method not allowed']);
        }
        break;

    case 'api/sales':
        $apiUser = authenticateApi();
        $currentUser = $apiUser ?? $currentUser;
        if ($currentUser === null) {
            http_response_code(401);
            jsonResponse(['error' => 'Unauthorized']);
            break;
        }

        $id = isset($_GET['id']) ? (int) $_GET['id'] : null;
        if ($method === 'GET') {
            if ($id !== null && $id > 0) {
                // Assume salesController has a find method, but it might not. For now, list and filter.
                $filters = ['id' => $id];
                $sales = $salesController->listSales($filters, 1, 1);
                if (empty($sales['rows'])) {
                    jsonResponse(['error' => 'Sale not found'], 404);
                } else {
                    jsonResponse(['success' => true, 'data' => $sales['rows'][0]]);
                }
            } else {
                $filters = [];
                $page = isset($_GET['page']) ? (int) $_GET['page'] : 1;
                $perPage = isset($_GET['per_page']) ? (int) $_GET['per_page'] : 10;
                $sales = $salesController->listSales($filters, $page, $perPage);
                jsonResponse(['success' => true, 'data' => $sales['rows'], 'pagination' => $sales['pagination']]);
            }
        } elseif ($method === 'POST') {
            $input = getJsonBody();
            $result = $salesController->create($currentUser['id'], $input);
            jsonResponse($result, $result['success'] ? 201 : 422);
        } else {
            http_response_code(405);
            jsonResponse(['error' => 'Method not allowed']);
        }
        break;

    case 'api/offers':
        $apiUser = authenticateApi();
        $currentUser = $apiUser ?? $currentUser;
        if ($currentUser === null) {
            http_response_code(401);
            jsonResponse(['error' => 'Unauthorized']);
            break;
        }

        if ($method === 'GET') {
            $offers = $offersController->listActive();
            jsonResponse(['success' => true, 'data' => $offers]);
        } else {
            http_response_code(405);
            jsonResponse(['error' => 'Method not allowed']);
        }
        break;

    case 'api/product_requests':
        $apiUser = authenticateApi();
        $currentUser = $apiUser ?? $currentUser;
        if ($currentUser === null) {
            http_response_code(401);
            jsonResponse(['error' => 'Unauthorized']);
            break;
        }

        $id = isset($_GET['id']) ? (int) $_GET['id'] : null;
        if ($method === 'GET') {
            if ($id !== null && $id > 0) {
                $request = $productRequestController->get($id);
                if ($request === null) {
                    jsonResponse(['error' => 'Request not found'], 404);
                } else {
                    jsonResponse(['success' => true, 'data' => $request]);
                }
            } else {
                $filters = [];
                $page = isset($_GET['page']) ? (int) $_GET['page'] : 1;
                $perPage = isset($_GET['per_page']) ? (int) $_GET['per_page'] : 10;
                $requests = $productRequestController->list($filters, $page, $perPage);
                jsonResponse(['success' => true, 'data' => $requests['rows'], 'pagination' => $requests['pagination']]);
            }
        } elseif ($method === 'PUT') {
            if ($id === null || $id <= 0) {
                jsonResponse(['error' => 'ID required'], 400);
            } else {
                $input = getJsonBody();
                $result = $productRequestController->update($id, $input, $currentUser);
                jsonResponse($result, $result['success'] ? 200 : 422);
            }
        } else {
            http_response_code(405);
            jsonResponse(['error' => 'Method not allowed']);
        }
        break;

    case 'api/support_requests':
        $apiUser = authenticateApi();
        $currentUser = $apiUser ?? $currentUser;
        if ($currentUser === null) {
            http_response_code(401);
            jsonResponse(['error' => 'Unauthorized']);
            break;
        }

        $id = isset($_GET['id']) ? (int) $_GET['id'] : null;
        if ($method === 'GET') {
            if ($id !== null && $id > 0) {
                $request = $supportRequestController->find($id);
                if ($request === null) {
                    jsonResponse(['error' => 'Request not found'], 404);
                } else {
                    jsonResponse(['success' => true, 'data' => $request]);
                }
            } else {
                $filters = [];
                $page = isset($_GET['page']) ? (int) $_GET['page'] : 1;
                $perPage = isset($_GET['per_page']) ? (int) $_GET['per_page'] : 10;
                $requests = $supportRequestController->list($filters, $page, $perPage);
                jsonResponse(['success' => true, 'data' => $requests['rows'], 'pagination' => $requests['pagination']]);
            }
        } elseif ($method === 'PUT') {
            if ($id === null || $id <= 0) {
                jsonResponse(['error' => 'ID required'], 400);
            } else {
                $input = getJsonBody();
                $result = $supportRequestController->update($input, $currentUser);
                jsonResponse($result, $result['success'] ? 200 : 422);
            }
        } else {
            http_response_code(405);
            jsonResponse(['error' => 'Method not allowed']);
        }
        break;

    case 'api/iccid':
        $apiUser = authenticateApi();
        $currentUser = $apiUser ?? $currentUser;
        if ($currentUser === null) {
            http_response_code(401);
            jsonResponse(['error' => 'Unauthorized']);
            break;
        }

        if ($method === 'GET') {
            $page = isset($_GET['page']) ? (int) $_GET['page'] : 1;
            $perPage = isset($_GET['per_page']) ? (int) $_GET['per_page'] : 10;
            $stock = $iccidController->listPaginated($page, $perPage);
            jsonResponse(['success' => true, 'data' => $stock['rows'], 'pagination' => $stock['pagination']]);
        } elseif ($method === 'POST') {
            $input = getJsonBody();
            $result = $iccidController->create($input);
            jsonResponse($result, $result['success'] ? 201 : 422);
        } else {
            http_response_code(405);
            jsonResponse(['error' => 'Method not allowed']);
        }
        break;

    case 'api/reports':
        $apiUser = authenticateApi();
        $currentUser = $apiUser ?? $currentUser;
        if ($currentUser === null) {
            http_response_code(401);
            jsonResponse(['error' => 'Unauthorized']);
            break;
        }

        if ($method === 'GET') {
            $view = $_GET['view'] ?? 'daily';
            $filters = $_GET;
            $report = $reportsController->summary($view, $filters);
            jsonResponse(['success' => true, 'data' => $report]);
        } else {
            http_response_code(405);
            jsonResponse(['error' => 'Method not allowed']);
        }
        break;

    case 'api/discounts':
        $apiUser = authenticateApi();
        $currentUser = $apiUser ?? $currentUser;
        if ($currentUser === null) {
            http_response_code(401);
            jsonResponse(['error' => 'Unauthorized']);
            break;
        }

        $id = isset($_GET['id']) ? (int) $_GET['id'] : null;
        if ($method === 'GET') {
            if ($id !== null && $id > 0) {
                $discount = $discountController->find($id);
                if ($discount === null) {
                    jsonResponse(['error' => 'Discount not found'], 404);
                } else {
                    jsonResponse(['success' => true, 'data' => $discount]);
                }
            } else {
                $discounts = $discountController->listAll();
                jsonResponse(['success' => true, 'data' => $discounts]);
            }
        } elseif ($method === 'POST') {
            $input = getJsonBody();
            $result = $discountController->create($input);
            jsonResponse($result, $result['success'] ? 201 : 422);
        } elseif ($method === 'PUT') {
            if ($id === null || $id <= 0) {
                jsonResponse(['error' => 'ID required'], 400);
            } else {
                $active = isset($_GET['active']) ? (bool) $_GET['active'] : true;
                $result = $discountController->setStatus($id, $active);
                jsonResponse($result, $result['success'] ? 200 : 422);
            }
        } else {
            http_response_code(405);
            jsonResponse(['error' => 'Method not allowed']);
        }
        break;

    case 'api/pda_import':
        $apiUser = authenticateApi();
        $currentUser = $apiUser ?? $currentUser;
        if ($currentUser === null) {
            http_response_code(401);
            jsonResponse(['error' => 'Unauthorized']);
            break;
        }

        if ($method === 'POST') {
            $result = $pdaImportController->upload($_FILES, $_POST, $currentUser);
            jsonResponse($result, $result['success'] ? 200 : 422);
        } else {
            http_response_code(405);
            jsonResponse(['error' => 'Method not allowed']);
        }
        break;

    case 'api/auth':
        if ($method === 'POST') {
            $action = $_GET['action'] ?? 'login';
            if ($action === 'login') {
                $input = getJsonBody();
                $username = $input['username'] ?? '';
                $password = $input['password'] ?? '';
                $result = $authController->login(['username' => $username, 'password' => $password]);
                if ($result['success']) {
                    // Return session ID as token
                    jsonResponse(['success' => true, 'token' => session_id(), 'user' => $result['user'] ?? null]);
                } else {
                    jsonResponse(['success' => false, 'errors' => $result['errors'] ?? []], 401);
                }
            } else {
                jsonResponse(['error' => 'Action not supported'], 400);
            }
        } else {
            http_response_code(405);
            jsonResponse(['error' => 'Method not allowed']);
        }
        break;
}

    function sanitizeInternalUrl(?string $candidate, string $fallback = 'index.php?page=support_requests'): string
    {
        if (!is_string($candidate) || $candidate === '') {
            return $fallback;
        }

        $decoded = rawurldecode($candidate);
        $trimmed = trim($decoded);
        if ($trimmed === '') {
            return $fallback;
        }

        return str_starts_with($trimmed, 'index.php') ? $trimmed : $fallback;
    }

/**
 * @param array<string, mixed> $params
 */
function render(string $view, array $params = [], bool $layout = true): void
{
    $viewPath = __DIR__ . '/../views/' . $view . '.php';
    if (!file_exists($viewPath)) {
        throw new \RuntimeException('View non trovata: ' . $view);
    }

    $sessionToasts = pullFlashToasts();
    if (isset($params['initialToasts']) && is_array($params['initialToasts'])) {
        $params['initialToasts'] = array_merge($sessionToasts, $params['initialToasts']);
    } else {
        $params['initialToasts'] = $sessionToasts;
    }

    if ($layout && !isset($params['topbarNotifications'])) {
        $globalNotificationService = $GLOBALS['systemNotificationService'] ?? null;
        if ($globalNotificationService instanceof \App\Services\SystemNotificationService) {
            $userCandidate = $params['currentUser'] ?? null;
            $userIdForNotifications = null;
            if (is_array($userCandidate) && isset($userCandidate['id'])) {
                $userIdForNotifications = (int) $userCandidate['id'];
            }

            $limit = (int) ($GLOBALS['config']['notifications']['topbar_limit'] ?? 10);
            if ($limit <= 0) {
                $limit = 10;
            }

            $params['topbarNotifications'] = $globalNotificationService->getTopbarFeed($userIdForNotifications, $limit);
        }
    }

    if ($layout && !array_key_exists('isAdmin', $params)) {
        $defaultIsAdmin = false;
        if (isset($GLOBALS['authService']) && $GLOBALS['authService'] instanceof \App\Services\AuthService) {
            $defaultIsAdmin = $GLOBALS['authService']->hasRole('admin');
        }
        $params['isAdmin'] = $defaultIsAdmin;
    }

    if ($layout && !array_key_exists('enabledModules', $params)) {
        $params['enabledModules'] = $GLOBALS['enabledModules'] ?? null;
    }

    if ($layout && !array_key_exists('tenantLicenseLabel', $params)) {
        $license = $GLOBALS['tenantLicense'] ?? null;
        $planKey = $GLOBALS['tenantPlanKey'] ?? null;
        $labelValue = null;
        if (is_array($license)) {
            $labelValue = isset($license['label']) && trim((string) $license['label']) !== ''
                ? trim((string) $license['label'])
                : null;
            if ($labelValue === null && isset($license['code']) && trim((string) $license['code']) !== '') {
                $labelValue = trim((string) $license['code']);
            }
        }
        if ($labelValue === null && is_string($planKey) && $planKey !== '') {
            $labelValue = $planKey;
        }
        $params['tenantLicenseLabel'] = $labelValue;
    }

    if ($layout && !array_key_exists('tenantLicenseExpiresAt', $params)) {
        $license = $GLOBALS['tenantLicense'] ?? null;
        $expiresAt = null;
        if (is_array($license) && isset($license['expires_at']) && $license['expires_at'] !== null) {
            $expiresAt = (string) $license['expires_at'];
        }
        $params['tenantLicenseExpiresAt'] = $expiresAt;
    }

    extract($params, EXTR_SKIP);

    if ($layout) {
        ob_start();
        require $viewPath;
        $content = ob_get_clean();
        require __DIR__ . '/../views/layout.php';
    } else {
        require $viewPath;
    }
}

function formatRoleLabel(string $rawRole): string
{
    if ($rawRole === '') {
        return 'Operatore';
    }

    $normalized = str_replace('_', ' ', strtolower($rawRole));

    return ucwords($normalized);
}

/**
 * @return array<int, array<string, string>>
 */
function resolveProfileShortcuts(string $roleKey): array
{
    $map = [
        'admin' => [
            [
                'label' => 'Gestisci operatori',
                'description' => 'Crea o aggiorna gli account del team.',
                'href' => 'index.php?page=settings',
            ],
            [
                'label' => 'Consulta audit',
                'description' => 'Rivedi accessi e modifiche recenti.',
                'href' => 'index.php?page=settings&audit_open=1',
            ],
            [
                'label' => 'Sicurezza account',
                'description' => 'Configura MFA e codici di recupero personali.',
                'href' => 'index.php?page=security',
            ],
        ],
        'cassiere' => [
            [
                'label' => 'Nuova vendita',
                'description' => 'Apri la schermata per registrare una vendita.',
                'href' => 'index.php?page=sales_create',
            ],
            [
                'label' => 'Storico vendite',
                'description' => 'Consulta le operazioni effettuate finora.',
                'href' => 'index.php?page=sales_list',
            ],
            [
                'label' => 'Sicurezza account',
                'description' => 'Configura MFA e codici di recupero personali.',
                'href' => 'index.php?page=security',
            ],
        ],
    ];

    $normalized = strtolower($roleKey);

    return $map[$normalized] ?? [
        [
            'label' => 'Preferenze account',
            'description' => 'Aggiorna le informazioni del tuo profilo.',
            'href' => 'index.php?page=settings',
        ],
        [
            'label' => 'Sicurezza account',
            'description' => 'Configura MFA e codici di recupero personali.',
            'href' => 'index.php?page=security',
        ],
    ];
}

function resolveRoleSummary(string $roleKey): string
{
    return match (strtolower($roleKey)) {
        'admin' => 'Hai accesso completo alla piattaforma e puoi coordinare operatori, stock e reportistica.',
        'cassiere' => 'Gestisci il flusso vendite quotidiano e garantisci la correttezza del magazzino.',
        default => 'Visualizza e gestisci le attività legate al tuo ruolo operativo.',
    };
}

/**
 * @return array{total_sales:int,total_revenue:float,last_sale_at:?string,status_breakdown:array<string,int>}
 */
function buildUserProfileSalesSummary(PDO $pdo, int $userId): array
{
    $summary = [
        'total_sales' => 0,
        'total_revenue' => 0.0,
        'last_sale_at' => null,
        'status_breakdown' => [],
    ];

    if ($userId <= 0) {
        return $summary;
    }

    $completedStmt = $pdo->prepare(
        "SELECT COUNT(*) AS total_sales, COALESCE(SUM(total), 0) AS total_revenue, MAX(created_at) AS last_sale_at
         FROM sales
         WHERE user_id = :uid AND status = 'Completed'"
    );
    $completedStmt->execute([':uid' => $userId]);
    $completedRow = $completedStmt->fetch(PDO::FETCH_ASSOC);
    if (is_array($completedRow)) {
        $summary['total_sales'] = (int) ($completedRow['total_sales'] ?? 0);
        $summary['total_revenue'] = (float) ($completedRow['total_revenue'] ?? 0.0);
        $lastSale = $completedRow['last_sale_at'] ?? null;
        $summary['last_sale_at'] = $lastSale !== null ? (string) $lastSale : null;
    }

    $statusStmt = $pdo->prepare('SELECT status, COUNT(*) AS total FROM sales WHERE user_id = :uid GROUP BY status');
    $statusStmt->execute([':uid' => $userId]);
    while ($statusRow = $statusStmt->fetch(PDO::FETCH_ASSOC)) {
        if (!is_array($statusRow) || !isset($statusRow['status'])) {
            continue;
        }
        $status = (string) $statusRow['status'];
        $count = (int) ($statusRow['total'] ?? 0);
        $summary['status_breakdown'][$status] = $count;
    }

    return $summary;
}

function getDashboardMetrics(PDO $pdo, string $period = 'day'): array
{
    $tenantId = \App\Services\TenantContext::id();
    $bounds = resolvePeriodBounds($period, false);
    $previousBounds = resolvePeriodBounds($period, true);

    $metrics = [
        'period_label' => $period,
        'period_range' => [
            'start' => $bounds['start']->format('Y-m-d H:i:s'),
            'end' => $bounds['end']->format('Y-m-d H:i:s'),
        ],
    ];

    $iccidTotalStmt = $pdo->prepare('SELECT COUNT(*) FROM iccid_stock WHERE tenant_id = :tenant_id');
    $iccidTotalStmt->execute([':tenant_id' => $tenantId]);
    $metrics['iccid_total'] = (int) ($iccidTotalStmt->fetchColumn() ?: 0);

    $iccidAvailableStmt = $pdo->prepare("SELECT COUNT(*) FROM iccid_stock WHERE tenant_id = :tenant_id AND status = 'InStock'");
    $iccidAvailableStmt->execute([':tenant_id' => $tenantId]);
    $metrics['iccid_available'] = (int) ($iccidAvailableStmt->fetchColumn() ?: 0);

    $currentSales = aggregateSalesForPeriod($pdo, $bounds);
    $previousSales = aggregateSalesForPeriod($pdo, $previousBounds);

    $metrics['sales_count'] = $currentSales['count'];
    $metrics['revenue_sum'] = $currentSales['revenue'];
    $metrics['sales_today'] = $currentSales['count'];
    $metrics['revenue_today'] = $currentSales['revenue'];
    $metrics['average_ticket'] = $currentSales['count'] > 0 ? $currentSales['revenue'] / max(1, $currentSales['count']) : 0.0;
    $metrics['average_ticket_previous'] = $previousSales['count'] > 0 ? $previousSales['revenue'] / max(1, $previousSales['count']) : 0.0;

    $metrics['comparison'] = buildPeriodComparison($currentSales, $previousSales);

    $metrics['sales_trend'] = buildSalesTrend($pdo, 7);
    $metrics['campaign_performance'] = buildCampaignPerformance($pdo);
    $metrics['recent_events'] = fetchRecentEvents($pdo, 8);
    $metrics['operator_activity'] = fetchOperatorActivity($pdo, 6);

    $supportSummary = fetchSupportSummary($pdo);
    $customerIntelligence = buildCustomerIntelligence($pdo);
    $billingPipeline = fetchBillingPipeline($pdo);
    $forecast = buildSalesForecast($pdo, 7);
    $governance = fetchGovernanceSnapshot($pdo);

    $metrics['support_summary'] = $supportSummary;
    $metrics['customer_intelligence'] = $customerIntelligence;
    $metrics['billing'] = $billingPipeline;
    $metrics['forecast'] = $forecast;
    $metrics['governance'] = $governance;
    $metrics['analytics_overview'] = buildAnalyticsOverview(
        $metrics,
        $supportSummary,
        $customerIntelligence,
        $billingPipeline
    );

    return $metrics;
}

/**
 * @return array{start: DateTimeImmutable, end: DateTimeImmutable}
 */
function resolvePeriodBounds(string $period, bool $previous = false): array
{
    $period = in_array($period, ['day', 'month', 'year'], true) ? $period : 'day';
    $anchor = new DateTimeImmutable('today');

    switch ($period) {
        case 'year':
            $start = $anchor->setDate((int) $anchor->format('Y'), 1, 1);
            if ($previous) {
                $start = $start->sub(new DateInterval('P1Y'));
            }
            $end = $start->setDate((int) $start->format('Y'), 12, 31)->setTime(23, 59, 59);
            break;

        case 'month':
            $start = $anchor->modify('first day of this month');
            if ($previous) {
                $start = $start->sub(new DateInterval('P1M'));
            }
            $start = $start->setTime(0, 0, 0);
            $end = $start->modify('last day of this month')->setTime(23, 59, 59);
            break;

        default:
            $start = $previous ? $anchor->sub(new DateInterval('P1D')) : $anchor;
            $start = $start->setTime(0, 0, 0);
            $end = $start->setTime(23, 59, 59);
            break;
    }

    return [
        'start' => $start,
        'end' => $end,
    ];
}

/**
 * @param array{start: DateTimeImmutable, end: DateTimeImmutable} $bounds
 * @return array{count:int,revenue:float,discount:float,paid:float,balance_due:float}
 */
function aggregateSalesForPeriod(PDO $pdo, array $bounds): array
{
    $tenantId = \App\Services\TenantContext::id();
    $stmt = $pdo->prepare(
        'SELECT
            SUM(CASE WHEN status = "Completed" THEN 1 ELSE 0 END) AS sales_count,
            COALESCE(SUM(CASE WHEN status = "Completed" THEN total ELSE 0 END), 0) AS revenue_total,
            COALESCE(SUM(CASE WHEN status = "Completed" THEN discount ELSE 0 END), 0) AS discount_total,
            COALESCE(SUM(CASE WHEN status = "Completed" THEN total_paid ELSE 0 END), 0) AS paid_total,
            COALESCE(SUM(CASE WHEN status = "Completed" THEN balance_due ELSE 0 END), 0) AS balance_due_total
         FROM sales
         WHERE tenant_id = :tenant_id AND created_at BETWEEN :start AND :end'
    );

    $stmt?->execute([
        ':tenant_id' => $tenantId,
        ':start' => $bounds['start']->format('Y-m-d H:i:s'),
        ':end' => $bounds['end']->format('Y-m-d H:i:s'),
    ]);

    $row = $stmt !== false ? $stmt->fetch(PDO::FETCH_ASSOC) : null;

    return [
        'count' => (int) ($row['sales_count'] ?? 0),
        'revenue' => (float) ($row['revenue_total'] ?? 0.0),
        'discount' => (float) ($row['discount_total'] ?? 0.0),
        'paid' => (float) ($row['paid_total'] ?? 0.0),
        'balance_due' => (float) ($row['balance_due_total'] ?? 0.0),
    ];
}

/**
 * @param array{count:int,revenue:float,discount:float,paid:float,balance_due:float} $current
 * @param array{count:int,revenue:float,discount:float,paid:float,balance_due:float} $previous
 * @return array<string, array<string, int|float|string|null>>
 */
function buildPeriodComparison(array $current, array $previous): array
{
    return [
        'sales' => calculateDelta($current['count'], $previous['count']),
        'revenue' => calculateDelta($current['revenue'], $previous['revenue']),
        'discount' => calculateDelta($current['discount'], $previous['discount']),
        'balance_due' => calculateDelta($current['balance_due'], $previous['balance_due']),
    ];
}

/**
 * @return array{current:float,previous:float,absolute:float,percent:?float,direction:string}
 */
function calculateDelta(float $current, float $previous): array
{
    $absolute = $current - $previous;
    $percent = $previous !== 0.0 ? ($absolute / $previous) * 100 : null;
    $direction = 'flat';
    if ($absolute > 0.0001) {
        $direction = 'up';
    } elseif ($absolute < -0.0001) {
        $direction = 'down';
    }

    return [
        'current' => $current,
        'previous' => $previous,
        'absolute' => $absolute,
        'percent' => $percent,
        'direction' => $direction,
    ];
}

/**
 * @return array<string, mixed>
 */
function fetchSupportSummary(PDO $pdo): array
{
    $tenantId = \App\Services\TenantContext::id();
    $statuses = ['Open', 'InProgress', 'Completed', 'Cancelled'];
    $summary = array_fill_keys($statuses, 0);

    $stmt = $pdo->prepare(
        'SELECT status, COUNT(*) AS total
         FROM customer_support_requests
         WHERE tenant_id = :tenant_id
         GROUP BY status'
    );
    $stmt->execute([':tenant_id' => $tenantId]);

    if ($stmt !== false) {
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $status = $row['status'] ?? null;
            if (is_string($status) && isset($summary[$status])) {
                $summary[$status] = (int) ($row['total'] ?? 0);
            }
        }
    }

        $openBreachesStmt = $pdo->prepare(
                "SELECT COUNT(*) FROM customer_support_requests
                 WHERE tenant_id = :tenant_id
                     AND status IN ('Open','InProgress')
                     AND created_at < DATE_SUB(NOW(), INTERVAL 48 HOUR)"
        );
        $openBreachesStmt->execute([':tenant_id' => $tenantId]);

    $openBreaches = (int) ($openBreachesStmt !== false ? ($openBreachesStmt->fetchColumn() ?: 0) : 0);

    $openTotal = $summary['Open'] + $summary['InProgress'];

    return [
        'by_status' => $summary,
        'open_total' => $openTotal,
        'breaches' => [
            'open_over_48h' => $openBreaches,
        ],
    ];
}

/**
 * @return array<string, mixed>
 */
function buildCustomerIntelligence(PDO $pdo): array
{
    $tenantId = \App\Services\TenantContext::id();
    $summary = [
        'total_customers' => 0,
        'portal_users' => 0,
        'active_last_30' => 0,
        'new_last_30' => 0,
        'active_portal_last_30' => 0,
    ];

    $totalCustomersStmt = $pdo->prepare('SELECT COUNT(*) FROM customers WHERE tenant_id = :tenant_id');
    $totalCustomersStmt->execute([':tenant_id' => $tenantId]);
    $summary['total_customers'] = (int) ($totalCustomersStmt->fetchColumn() ?: 0);

    $portalUsersStmt = $pdo->prepare('SELECT COUNT(*) FROM customer_portal_accounts WHERE tenant_id = :tenant_id');
    $portalUsersStmt->execute([':tenant_id' => $tenantId]);
    $summary['portal_users'] = (int) ($portalUsersStmt->fetchColumn() ?: 0);

    $activePortalStmt = $pdo->prepare(
        'SELECT COUNT(*) FROM customer_portal_accounts WHERE tenant_id = :tenant_id AND last_login_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)'
    );
    $activePortalStmt->execute([':tenant_id' => $tenantId]);
    $summary['active_portal_last_30'] = (int) ($activePortalStmt->fetchColumn() ?: 0);

    $newCustomersStmt = $pdo->prepare(
        'SELECT COUNT(*) FROM customers WHERE tenant_id = :tenant_id AND created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)'
    );
    $newCustomersStmt->execute([':tenant_id' => $tenantId]);
    $summary['new_last_30'] = (int) ($newCustomersStmt->fetchColumn() ?: 0);

        $activeStmt = $pdo->prepare(
                'SELECT COUNT(DISTINCT customer_id)
                 FROM sales
                 WHERE tenant_id = :tenant_id
                     AND customer_id IS NOT NULL
                     AND status = "Completed"
                     AND created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)'
        );
        $activeStmt?->execute([':tenant_id' => $tenantId]);
    $summary['active_last_30'] = (int) ($activeStmt !== false ? ($activeStmt->fetchColumn() ?: 0) : 0);

    $topCustomersStmt = $pdo->prepare(
        'SELECT
            COALESCE(c.fullname, s.customer_name, "Cliente" ) AS customer_name,
            c.id AS customer_id,
            COUNT(*) AS orders,
            COALESCE(SUM(s.total), 0) AS revenue,
            MAX(s.created_at) AS last_purchase
         FROM sales s
         LEFT JOIN customers c ON c.id = s.customer_id AND c.tenant_id = :tenant_id_customers
         WHERE s.tenant_id = :tenant_id_sales AND s.status = "Completed"
         GROUP BY c.id, customer_name
         ORDER BY revenue DESC
         LIMIT 5'
    );
    $topCustomersStmt->execute([
        ':tenant_id_customers' => $tenantId,
        ':tenant_id_sales' => $tenantId,
    ]);
    $topCustomers = $topCustomersStmt !== false ? $topCustomersStmt->fetchAll(PDO::FETCH_ASSOC) : [];

    $atRiskStmt = $pdo->prepare(
        'SELECT
            COALESCE(c.fullname, s.customer_name, "Cliente") AS customer_name,
            MAX(s.created_at) AS last_purchase,
            COUNT(*) AS orders,
            COALESCE(SUM(s.total), 0) AS revenue
         FROM sales s
         LEFT JOIN customers c ON c.id = s.customer_id AND c.tenant_id = :tenant_id_customers
         WHERE s.tenant_id = :tenant_id_sales AND s.status = "Completed"
         GROUP BY c.id, customer_name
         HAVING MAX(s.created_at) < DATE_SUB(NOW(), INTERVAL 60 DAY)
         ORDER BY last_purchase ASC
         LIMIT 5'
    );
    $atRiskStmt->execute([
        ':tenant_id_customers' => $tenantId,
        ':tenant_id_sales' => $tenantId,
    ]);
    $atRisk = $atRiskStmt !== false ? $atRiskStmt->fetchAll(PDO::FETCH_ASSOC) : [];

    $recentCustomersStmt = $pdo->prepare(
        'SELECT fullname, email, created_at
         FROM customers
         WHERE tenant_id = :tenant_id
         ORDER BY created_at DESC
         LIMIT 5'
    );
    $recentCustomersStmt->execute([':tenant_id' => $tenantId]);
    $recentCustomers = $recentCustomersStmt !== false ? $recentCustomersStmt->fetchAll(PDO::FETCH_ASSOC) : [];

    return [
        'summary' => $summary,
        'top_customers' => $topCustomers,
        'at_risk_customers' => $atRisk,
        'recent_customers' => $recentCustomers,
    ];
}

/**
 * @return array<string, mixed>
 */
function fetchBillingPipeline(PDO $pdo): array
{
    $tenantId = \App\Services\TenantContext::id();
    $pendingPaymentsStmt = $pdo->prepare(
        'SELECT COUNT(*) AS total, COALESCE(SUM(amount), 0) AS amount
         FROM customer_payments
         WHERE tenant_id = :tenant_id AND status = "Pending"'
    );
    $pendingPaymentsStmt->execute([':tenant_id' => $tenantId]);
    $pendingPaymentsRow = $pendingPaymentsStmt !== false ? $pendingPaymentsStmt->fetch(PDO::FETCH_ASSOC) : null;

    $overdueStmt = $pdo->prepare(
        "SELECT COUNT(*) AS total, COALESCE(SUM(balance_due), 0) AS amount
         FROM sales
         WHERE tenant_id = :tenant_id
           AND status = 'Completed'
           AND payment_status IN ('Overdue','Pending','Partial')
           AND due_date IS NOT NULL
           AND due_date < CURRENT_DATE()
           AND balance_due > 0"
    );
    $overdueStmt->execute([':tenant_id' => $tenantId]);
    $overdueRow = $overdueStmt !== false ? $overdueStmt->fetch(PDO::FETCH_ASSOC) : null;

    $dueSoonStmt = $pdo->prepare(
        "SELECT COUNT(*) AS total, COALESCE(SUM(balance_due), 0) AS amount
         FROM sales
         WHERE tenant_id = :tenant_id
           AND status = 'Completed'
           AND payment_status IN ('Pending','Partial')
           AND due_date BETWEEN CURRENT_DATE() AND DATE_ADD(CURRENT_DATE(), INTERVAL 7 DAY)
           AND balance_due > 0"
    );
    $dueSoonStmt->execute([':tenant_id' => $tenantId]);
    $dueSoonRow = $dueSoonStmt !== false ? $dueSoonStmt->fetch(PDO::FETCH_ASSOC) : null;

    return [
        'pending_payments' => [
            'count' => (int) ($pendingPaymentsRow['total'] ?? 0),
            'amount' => (float) ($pendingPaymentsRow['amount'] ?? 0.0),
        ],
        'overdue_invoices' => [
            'count' => (int) ($overdueRow['total'] ?? 0),
            'amount' => (float) ($overdueRow['amount'] ?? 0.0),
        ],
        'due_next_7_days' => [
            'count' => (int) ($dueSoonRow['total'] ?? 0),
            'amount' => (float) ($dueSoonRow['amount'] ?? 0.0),
        ],
    ];
}

/**
 * @return array<string, mixed>
 */
function buildSalesForecast(PDO $pdo, int $horizonDays = 7): array
{
    $tenantId = \App\Services\TenantContext::id();
    $horizonDays = max(1, min($horizonDays, 14));
    $lookbackDays = 28;
    $end = new DateTimeImmutable('today 23:59:59');
    $start = $end->sub(new DateInterval('P' . ($lookbackDays - 1) . 'D'))->setTime(0, 0, 0);

    $stmt = $pdo->prepare(
        'SELECT DATE(created_at) AS day,
                SUM(CASE WHEN status = "Completed" THEN 1 ELSE 0 END) AS sales_count,
                COALESCE(SUM(CASE WHEN status = "Completed" THEN total ELSE 0 END), 0) AS revenue_total
         FROM sales
         WHERE tenant_id = :tenant_id AND created_at BETWEEN :start AND :end
         GROUP BY DATE(created_at)
         ORDER BY DATE(created_at) ASC'
    );
    $stmt?->execute([
        ':tenant_id' => $tenantId,
        ':start' => $start->format('Y-m-d H:i:s'),
        ':end' => $end->format('Y-m-d H:i:s'),
    ]);

    $rows = $stmt !== false ? $stmt->fetchAll(PDO::FETCH_ASSOC) : [];
    $map = [];
    foreach ($rows as $row) {
        $map[(string) ($row['day'] ?? '')] = [
            'sales' => (int) ($row['sales_count'] ?? 0),
            'revenue' => (float) ($row['revenue_total'] ?? 0.0),
        ];
    }

    $period = new DatePeriod($start, new DateInterval('P1D'), $end->add(new DateInterval('P1D')));
    $dailySales = [];
    $dailyRevenue = [];
    foreach ($period as $date) {
        $key = $date->format('Y-m-d');
        $entry = $map[$key] ?? ['sales' => 0, 'revenue' => 0.0];
        $dailySales[] = (int) $entry['sales'];
        $dailyRevenue[] = (float) $entry['revenue'];
    }

    $daysCount = count($dailySales);
    $totalSales = array_sum($dailySales);
    $totalRevenue = array_sum($dailyRevenue);
    $avgSales = $daysCount > 0 ? $totalSales / $daysCount : 0.0;
    $avgRevenue = $daysCount > 0 ? $totalRevenue / $daysCount : 0.0;

    $expectedSales = (int) round($avgSales * $horizonDays);
    $expectedRevenue = $avgRevenue * $horizonDays;

    $stdSales = 0.0;
    if ($daysCount > 0) {
        $variance = 0.0;
        foreach ($dailySales as $value) {
            $variance += pow($value - $avgSales, 2);
        }
        $variance /= max($daysCount, 1);
        $stdSales = sqrt($variance);
    }
    $coeff = ($avgSales > 0.0) ? ($stdSales / max($avgSales, 1)) : null;
    $confidence = 'bassa';
    if ($coeff === null) {
        $confidence = $avgSales > 0.0 ? 'media' : 'bassa';
    } elseif ($coeff < 0.35) {
        $confidence = 'alta';
    } elseif ($coeff < 0.6) {
        $confidence = 'media';
    }

    $recentWindow = array_slice($dailySales, -7) ?: [];
    $previousWindow = array_slice($dailySales, -14, 7) ?: [];
    $recentAvg = $recentWindow !== [] ? array_sum($recentWindow) / count($recentWindow) : 0.0;
    $previousAvg = $previousWindow !== [] ? array_sum($previousWindow) / count($previousWindow) : 0.0;
    $trendDirection = calculateDelta($recentAvg, $previousAvg)['direction'];

    return [
        'lookback_days' => $lookbackDays,
        'horizon_days' => $horizonDays,
        'avg_daily_sales' => $avgSales,
        'avg_daily_revenue' => $avgRevenue,
        'expected_sales' => $expectedSales,
        'expected_revenue' => $expectedRevenue,
        'confidence' => $confidence,
        'trend_direction' => $trendDirection,
        'recent_average_sales' => $recentAvg,
    ];
}

/**
 * @return array<string, mixed>
 */
function fetchGovernanceSnapshot(PDO $pdo): array
{
    $tenantId = \App\Services\TenantContext::id();
    $activePoliciesStmt = $pdo->prepare('SELECT COUNT(*) FROM privacy_policies WHERE tenant_id = :tenant_id AND is_active = 1');
    $activePoliciesStmt->execute([':tenant_id' => $tenantId]);
    $activePolicies = (int) ($activePoliciesStmt->fetchColumn() ?: 0);
    $latestPolicyStmt = $pdo->prepare(
        'SELECT version, updated_at
         FROM privacy_policies
         WHERE tenant_id = :tenant_id
         ORDER BY updated_at DESC
         LIMIT 1'
    );
    $latestPolicyStmt->execute([':tenant_id' => $tenantId]);
    $latestPolicy = $latestPolicyStmt !== false ? $latestPolicyStmt->fetch(PDO::FETCH_ASSOC) : null;

    $portalAccountsStmt = $pdo->prepare('SELECT COUNT(*) FROM customer_portal_accounts WHERE tenant_id = :tenant_id');
    $portalAccountsStmt->execute([':tenant_id' => $tenantId]);
    $portalAccounts = (int) ($portalAccountsStmt->fetchColumn() ?: 0);

    $acceptancesTotalStmt = $pdo->prepare('SELECT COUNT(*) FROM privacy_policy_acceptances WHERE tenant_id = :tenant_id');
    $acceptancesTotalStmt->execute([':tenant_id' => $tenantId]);
    $acceptancesTotal = (int) ($acceptancesTotalStmt->fetchColumn() ?: 0);

    $uniqueAcceptancesStmt = $pdo->prepare(
        'SELECT COUNT(DISTINCT portal_account_id) FROM privacy_policy_acceptances WHERE tenant_id = :tenant_id'
    );
    $uniqueAcceptancesStmt->execute([':tenant_id' => $tenantId]);
    $uniqueAcceptances = (int) ($uniqueAcceptancesStmt->fetchColumn() ?: 0);
    $acceptanceRate = $portalAccounts > 0 ? ($uniqueAcceptances / $portalAccounts) * 100 : null;
    $pendingAcceptances = max(0, $portalAccounts - $uniqueAcceptances);

    $auditLast30Stmt = $pdo->prepare(
        'SELECT COUNT(*) FROM audit_log WHERE tenant_id = :tenant_id AND created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)'
    );
    $auditLast30Stmt->execute([':tenant_id' => $tenantId]);
    $auditLast30 = (int) ($auditLast30Stmt->fetchColumn() ?: 0);

    return [
        'active_policies' => $activePolicies,
        'latest_version' => $latestPolicy['version'] ?? null,
        'last_policy_update' => $latestPolicy['updated_at'] ?? null,
        'portal_accounts' => $portalAccounts,
        'acceptances_total' => $acceptancesTotal,
        'unique_acceptances' => $uniqueAcceptances,
        'acceptance_rate' => $acceptanceRate,
        'pending_acceptances' => $pendingAcceptances,
        'audit_events_last_30' => $auditLast30,
    ];
}

/**
 * @param array<string, mixed> $metrics
 * @param array<string, mixed> $supportSummary
 * @param array<string, mixed> $customerIntelligence
 * @param array<string, mixed> $billing
 * @return array<string, mixed>
 */
function buildAnalyticsOverview(array $metrics, array $supportSummary, array $customerIntelligence, array $billing): array
{
    $cards = [];

    $salesDelta = $metrics['comparison']['sales'] ?? calculateDelta(0.0, 0.0);
    $revenueDelta = $metrics['comparison']['revenue'] ?? calculateDelta(0.0, 0.0);

    $avgTicket = (float) ($metrics['average_ticket'] ?? 0.0);
    $avgTicketPrev = (float) ($metrics['average_ticket_previous'] ?? 0.0);
    $ticketDelta = calculateDelta($avgTicket, $avgTicketPrev);

    $customerSummary = $customerIntelligence['summary'] ?? [];
    $openSupport = (int) ($supportSummary['open_total'] ?? 0);
    $breaches = (int) ($supportSummary['breaches']['open_over_48h'] ?? 0);
    $overdueCount = (int) ($billing['overdue_invoices']['count'] ?? 0);
    $overdueAmount = (float) ($billing['overdue_invoices']['amount'] ?? 0.0);

    $stockTotal = (int) ($metrics['iccid_total'] ?? 0);
    $stockAvailable = (int) ($metrics['iccid_available'] ?? 0);
    $utilization = $stockTotal > 0 ? (1 - ($stockAvailable / max(1, $stockTotal))) * 100 : 0.0;

    $cards[] = [
        'id' => 'sales_volume',
        'label' => 'Vendite completate',
        'value' => (int) ($metrics['sales_count'] ?? 0),
        'meta' => 'Ticket medio € ' . number_format($avgTicket, 2, ',', '.'),
        'delta' => $salesDelta,
        'format' => 'number',
    ];

    $cards[] = [
        'id' => 'revenue_total',
        'label' => 'Fatturato periodo',
        'value' => (float) ($metrics['revenue_sum'] ?? 0.0),
        'meta' => 'Incassato vs precedente: ' . formatDeltaBadgeMeta($revenueDelta),
        'delta' => $revenueDelta,
        'format' => 'currency',
    ];

    $cards[] = [
        'id' => 'average_ticket',
        'label' => 'Ticket medio',
        'value' => $avgTicket,
        'meta' => 'Periodo precedente € ' . number_format($avgTicketPrev, 2, ',', '.'),
        'delta' => $ticketDelta,
        'format' => 'currency',
    ];

    $cards[] = [
        'id' => 'active_customers',
        'label' => 'Clienti attivi 30g',
        'value' => (int) ($customerSummary['active_last_30'] ?? 0),
        'meta' => 'Nuovi clienti 30g: ' . (int) ($customerSummary['new_last_30'] ?? 0),
        'delta' => null,
        'format' => 'number',
    ];

    $cards[] = [
        'id' => 'support_backlog',
        'label' => 'Ticket da evadere',
        'value' => $openSupport,
        'meta' => 'Fuori SLA: ' . $breaches,
        'delta' => null,
        'format' => 'number',
    ];

    $cards[] = [
        'id' => 'billing_risk',
        'label' => 'Partite critiche',
        'value' => $overdueCount,
        'meta' => 'Scaduto € ' . number_format($overdueAmount, 2, ',', '.'),
        'delta' => null,
        'format' => 'number',
    ];

    $cards[] = [
        'id' => 'inventory_health',
        'label' => 'Saturazione stock',
        'value' => $utilization,
        'meta' => 'Disponibili SIM: ' . $stockAvailable,
        'delta' => null,
        'format' => 'percent',
    ];

    return [
        'cards' => array_slice($cards, 0, 6),
    ];
}

/**
 * @param array<string, mixed> $metrics
 * @param array<int, array<string, mixed>> $stockAlerts
 * @param array<int, array<string, mixed>> $productAlerts
 * @param array<int, array<string, mixed>> $providerInsights
 * @param array<int, array<string, mixed>> $productInsights
 * @param array<string, mixed> $campaignPerformance
 * @param array<string, mixed> $supportSummary
 * @param array<string, mixed> $billing
 * @return array<string, mixed>
 */
function buildOperationalPulse(
    array $metrics,
    array $stockAlerts,
    array $productAlerts,
    array $providerInsights,
    array $productInsights,
    array $campaignPerformance,
    array $supportSummary,
    array $billing
): array {
    $expiringCampaigns = [];
    foreach ($campaignPerformance['items'] ?? [] as $item) {
        $days = $item['ends_in_days'] ?? null;
        if (!empty($item['is_active']) && $days !== null && is_numeric($days) && (int) $days >= 0 && (int) $days <= 7) {
            $expiringCampaigns[] = [
                'name' => (string) ($item['name'] ?? ''),
                'days' => (int) $days,
                'revenue' => (float) ($item['revenue_total'] ?? 0.0),
            ];
        }
    }

    usort($expiringCampaigns, static fn(array $a, array $b): int => $a['days'] <=> $b['days']);

    $lowStock = array_filter($providerInsights, static fn(array $info): bool => !empty($info['below_threshold']));
    $lowStockNames = array_map(static fn(array $info): string => (string) ($info['provider_name'] ?? ''), $lowStock);

    $lowStockProducts = array_filter($productInsights, static fn(array $info): bool => !empty($info['below_threshold']));
    $lowStockProductNames = array_map(static fn(array $info): string => (string) ($info['product_name'] ?? ''), $lowStockProducts);

    return [
        'provider_alerts' => $stockAlerts,
        'product_alerts' => $productAlerts,
        'low_stock_providers' => array_slice(array_filter($lowStockNames), 0, 5),
        'low_stock_products' => array_slice(array_filter($lowStockProductNames), 0, 5),
        'expiring_campaigns' => array_slice($expiringCampaigns, 0, 4),
        'recent_events' => $metrics['recent_events'] ?? [],
        'operator_activity' => $metrics['operator_activity'] ?? [],
        'support_summary' => $supportSummary,
        'billing' => $billing,
    ];
}

function formatDeltaBadgeMeta(array $delta): string
{
    if ($delta['percent'] === null) {
        $absolute = (float) ($delta['absolute'] ?? 0.0);
        if (abs($absolute) < 0.01) {
            return 'invariato';
        }
        $symbol = $absolute > 0 ? '+ ' : '- ';
        return $symbol . number_format(abs($absolute), 2, ',', '.');
    }

    $percentValue = (float) $delta['percent'];
    $symbol = $percentValue > 0 ? '+' : '';
    return $symbol . number_format($percentValue, 1, ',', '.') . '%';
}

function buildSalesTrend(PDO $pdo, int $days = 7): array
{
    $tenantId = \App\Services\TenantContext::id();
    $days = max(2, min($days, 30));
    $endDate = new DateTimeImmutable('today 23:59:59');
    $startDate = $endDate->sub(new DateInterval('P' . ($days - 1) . 'D'))->setTime(0, 0);

    $stmt = $pdo->prepare(
        'SELECT DATE(created_at) AS day,
                SUM(CASE WHEN status = "Completed" THEN 1 ELSE 0 END) AS sales_count,
                COALESCE(SUM(CASE WHEN status = "Completed" THEN total ELSE 0 END), 0) AS revenue_total
         FROM sales
         WHERE tenant_id = :tenant_id AND created_at BETWEEN :start AND :end
         GROUP BY DATE(created_at)
         ORDER BY DATE(created_at) ASC'
    );
    $stmt->execute([
        ':tenant_id' => $tenantId,
        ':start' => $startDate->format('Y-m-d H:i:s'),
        ':end' => $endDate->format('Y-m-d H:i:s'),
    ]);
    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
    $map = [];
    foreach ($rows as $row) {
        $map[(string) $row['day']] = [
            'count' => (int) ($row['sales_count'] ?? 0),
            'revenue' => (float) ($row['revenue_total'] ?? 0.0),
        ];
    }

    $period = new DatePeriod($startDate, new DateInterval('P1D'), $endDate->add(new DateInterval('P1D')));
    $points = [];
    $maxCount = 0;
    $maxRevenue = 0.0;
    $totalCount = 0;
    $totalRevenue = 0.0;

    foreach ($period as $date) {
        $key = $date->format('Y-m-d');
        $value = $map[$key] ?? ['count' => 0, 'revenue' => 0.0];
        $count = (int) $value['count'];
        $revenue = (float) $value['revenue'];
        $maxCount = max($maxCount, $count);
        $maxRevenue = max($maxRevenue, $revenue);
        $totalCount += $count;
        $totalRevenue += $revenue;
        $points[] = [
            'date' => $key,
            'label' => $date->format('d/m'),
            'count' => $count,
            'revenue' => $revenue,
        ];
    }

    if ($maxCount > 0 || $maxRevenue > 0) {
        foreach ($points as &$point) {
            $point['count_pct'] = $maxCount > 0 ? (int) round(($point['count'] / $maxCount) * 100) : 0;
            $point['revenue_pct'] = $maxRevenue > 0 ? (int) round(($point['revenue'] / $maxRevenue) * 100) : 0;
        }
        unset($point);
    }

    return [
        'points' => $points,
        'max_count' => $maxCount,
        'max_revenue' => $maxRevenue,
        'total_count' => $totalCount,
        'total_revenue' => $totalRevenue,
        'start_label' => $startDate->format('d/m'),
        'end_label' => $endDate->format('d/m'),
    ];
}

function buildCampaignPerformance(PDO $pdo): array
{
    $tenantId = \App\Services\TenantContext::id();
    $sql = 'SELECT
                dc.id,
                dc.name,
                dc.type,
                dc.value,
                dc.is_active,
                dc.starts_at,
                dc.ends_at,
                SUM(CASE WHEN s.status = "Completed" THEN 1 ELSE 0 END) AS sales_count,
                COALESCE(SUM(CASE WHEN s.status = "Completed" THEN s.total ELSE 0 END), 0) AS revenue_total,
                COALESCE(SUM(CASE WHEN s.status = "Completed" THEN s.discount ELSE 0 END), 0) AS discount_total,
                SUM(CASE WHEN s.status = "Completed" AND DATE(s.created_at) = CURRENT_DATE() THEN 1 ELSE 0 END) AS sales_today
            FROM discount_campaigns dc
                LEFT JOIN sales s ON s.discount_campaign_id = dc.id AND s.tenant_id = :tenant_id_sales
                WHERE dc.tenant_id = :tenant_id_campaign
            GROUP BY dc.id
            ORDER BY dc.is_active DESC, dc.ends_at IS NULL DESC, dc.ends_at ASC, dc.created_at DESC';

    $stmt = $pdo->prepare($sql);
            $stmt->execute([
            ':tenant_id_sales' => $tenantId,
            ':tenant_id_campaign' => $tenantId,
            ]);
    $rows = $stmt !== false ? $stmt->fetchAll(PDO::FETCH_ASSOC) : [];

    $items = [];
    $activeTotal = 0;
    $discountAggregate = 0.0;

    foreach ($rows as $row) {
        $isActive = ((int) ($row['is_active'] ?? 0)) === 1;
        if ($isActive) {
            $activeTotal++;
        }
        $discountAggregate += (float) ($row['discount_total'] ?? 0.0);

        $endsAt = null;
        $daysToEnd = null;
        if (!empty($row['ends_at'])) {
            try {
                $endsAt = new DateTimeImmutable((string) $row['ends_at']);
                $diff = (new DateTimeImmutable('today'))->diff($endsAt);
                $daysToEnd = (int) $diff->format('%r%a');
            } catch (\Throwable $exception) {
                $endsAt = null;
            }
        }

        $items[] = [
            'id' => (int) ($row['id'] ?? 0),
            'name' => (string) ($row['name'] ?? ''),
            'type' => (string) ($row['type'] ?? 'Fixed'),
            'value' => (float) ($row['value'] ?? 0.0),
            'is_active' => $isActive,
            'starts_at' => $row['starts_at'] ?? null,
            'ends_at' => $row['ends_at'] ?? null,
            'ends_in_days' => $daysToEnd,
            'sales_count' => (int) ($row['sales_count'] ?? 0),
            'sales_today' => (int) ($row['sales_today'] ?? 0),
            'revenue_total' => (float) ($row['revenue_total'] ?? 0.0),
            'discount_total' => (float) ($row['discount_total'] ?? 0.0),
        ];
    }

    return [
        'items' => $items,
        'active_total' => $activeTotal,
        'discount_total' => $discountAggregate,
    ];
}

function paginateAuditLogs(PDO $pdo, int $page, int $perPage = 10, ?int $tenantId = null): array
{
    $page = max(1, $page);
    $perPage = max(5, min($perPage, 25));

    if ($tenantId !== null) {
        $countStmt = $pdo->prepare('SELECT COUNT(*) FROM audit_log WHERE tenant_id = :tenant_id');
        $countStmt->execute([':tenant_id' => $tenantId]);
        $total = (int) ($countStmt->fetchColumn() ?: 0);
    } else {
        $countStmt = $pdo->query('SELECT COUNT(*) FROM audit_log');
        $total = (int) ($countStmt !== false ? ($countStmt->fetchColumn() ?: 0) : 0);
    }
    $pages = $total > 0 ? (int) ceil($total / $perPage) : 1;
    if ($page > $pages) {
        $page = $pages;
    }

    $rows = [];
    if ($total > 0) {
        $offset = ($page - 1) * $perPage;
        $sql = 'SELECT al.id, al.action, al.description, al.created_at, u.fullname, u.username
                FROM audit_log al
                LEFT JOIN users u ON u.id = al.user_id';
        if ($tenantId !== null) {
            $sql .= ' WHERE al.tenant_id = :tenant_id';
        }
        $sql .= ' ORDER BY al.created_at DESC LIMIT :limit OFFSET :offset';
        $stmt = $pdo->prepare($sql);
        if ($stmt !== false) {
            if ($tenantId !== null) {
                $stmt->bindValue(':tenant_id', $tenantId, PDO::PARAM_INT);
            }
            $stmt->bindValue(':limit', $perPage, PDO::PARAM_INT);
            $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
            $stmt->execute();
            $rows = $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
        }
    }

    $mapped = array_map(static function (array $row): array {
        $user = $row['fullname'] ?? $row['username'] ?? null;
        $action = (string) ($row['action'] ?? '');
        $description = (string) ($row['description'] ?? '');
        $createdAtRaw = (string) ($row['created_at'] ?? '');
        $createdAtDisplay = $createdAtRaw;
        if ($createdAtRaw !== '') {
            try {
                $createdAtDisplay = (new DateTimeImmutable($createdAtRaw))->format('d/m/Y H:i');
            } catch (\Throwable $exception) {
                $createdAtDisplay = $createdAtRaw;
            }
        }

        return [
            'action' => $action,
            'action_label' => formatAuditActionLabel($action, $description),
            'description' => $description,
            'created_at' => $createdAtRaw,
            'created_at_display' => $createdAtDisplay,
            'user' => $user !== null ? (string) $user : null,
        ];
    }, $rows);

    return [
        'rows' => $mapped,
        'pagination' => [
            'page' => $page,
            'per_page' => $perPage,
            'total' => $total,
            'pages' => $pages,
            'has_prev' => $page > 1,
            'has_next' => $page < $pages,
        ],
    ];
}

function fetchRecentEvents(PDO $pdo, int $limit = 6): array
{
    $tenantId = \App\Services\TenantContext::id();
    $limit = max(1, min($limit, 20));
    $sql = 'SELECT al.action, al.description, al.created_at, u.fullname, u.username
        FROM audit_log al
        LEFT JOIN users u ON u.id = al.user_id
        WHERE al.tenant_id = :tenant_id
        ORDER BY al.created_at DESC
        LIMIT ' . $limit;
    $stmt = $pdo->prepare($sql);
    $stmt->execute([':tenant_id' => $tenantId]);
    $rows = $stmt !== false ? $stmt->fetchAll(PDO::FETCH_ASSOC) : [];

    return array_map(static function (array $row): array {
        $user = $row['fullname'] ?? $row['username'] ?? null;
        $actionCode = (string) ($row['action'] ?? '');
        $description = (string) ($row['description'] ?? '');
        return [
            'action' => $actionCode,
            'action_label' => formatAuditActionLabel($actionCode, $description),
            'description' => $description,
            'created_at' => (string) ($row['created_at'] ?? ''),
            'user' => $user !== null ? (string) $user : null,
        ];
    }, $rows);
}

function formatAuditActionLabel(string $action, string $description = ''): string
{
    $map = [
        'sale_create' => 'Vendita registrata',
        'sale_cancel' => 'Vendita annullata',
        'sale_refund' => 'Reso vendita',
    ];

    if (isset($map[$action])) {
        return $map[$action];
    }

    $label = str_replace('_', ' ', trim($action));
    $label = $label !== '' ? ucfirst($label) : ($description !== '' ? $description : 'Aggiornamento');
    return $label;
}

function fetchOperatorActivity(PDO $pdo, int $limit = 5): array
{
    $tenantId = \App\Services\TenantContext::id();
    $limit = max(1, min($limit, 20));
    $sql = 'SELECT s.id, s.created_at, s.total, s.discount, s.payment_method, s.status,
                   u.fullname, u.username
            FROM sales s
            LEFT JOIN users u ON u.id = s.user_id
            WHERE s.tenant_id = :tenant_id
            ORDER BY s.created_at DESC
            LIMIT ' . $limit;
    $stmt = $pdo->prepare($sql);
    $stmt->execute([':tenant_id' => $tenantId]);
    $rows = $stmt !== false ? $stmt->fetchAll(PDO::FETCH_ASSOC) : [];

    return array_map(static function (array $row): array {
        $user = $row['fullname'] ?? $row['username'] ?? null;
        return [
            'sale_id' => (int) ($row['id'] ?? 0),
            'created_at' => (string) ($row['created_at'] ?? ''),
            'total' => (float) ($row['total'] ?? 0.0),
            'discount' => (float) ($row['discount'] ?? 0.0),
            'payment_method' => (string) ($row['payment_method'] ?? ''),
            'status' => (string) ($row['status'] ?? ''),
            'user' => $user !== null ? (string) $user : null,
        ];
    }, $rows);
}

/**
 * @param array<int, array<string, mixed>> $providerInsights
 * @return array<int, array<string, mixed>>
 */
function buildStockRiskSummary(array $providerInsights): array
{
    if ($providerInsights === []) {
        return [];
    }

    $insights = array_map(static function (array $info): array {
        $daysCover = $info['days_cover'] ?? null;
        $daysCover = is_numeric($daysCover) ? (float) $daysCover : null;
        $riskLevel = 'ok';
        if (!empty($info['below_threshold'])) {
            $riskLevel = 'warning';
            if ($daysCover !== null && $daysCover < 3) {
                $riskLevel = 'critical';
            }
        }
        return [
            'provider_name' => (string) ($info['provider_name'] ?? ''),
            'current_stock' => (int) ($info['current_stock'] ?? 0),
            'threshold' => (int) ($info['threshold'] ?? 0),
            'average_daily_sales' => (float) ($info['average_daily_sales'] ?? 0.0),
            'days_cover' => $daysCover,
            'suggested_reorder' => (int) ($info['suggested_reorder'] ?? 0),
            'risk_level' => $riskLevel,
        ];
    }, $providerInsights);

    usort($insights, static function (array $a, array $b): int {
        $rank = ['critical' => 0, 'warning' => 1, 'ok' => 2];
        $riskA = $rank[$a['risk_level']] ?? 2;
        $riskB = $rank[$b['risk_level']] ?? 2;
        if ($riskA !== $riskB) {
            return $riskA <=> $riskB;
        }
        $coverA = $a['days_cover'] ?? PHP_FLOAT_MAX;
        $coverB = $b['days_cover'] ?? PHP_FLOAT_MAX;
        return $coverA <=> $coverB;
    });

    return array_slice($insights, 0, 5);
}

/**
 * @param array<int, array<string, mixed>> $productInsights
 * @return array<int, array<string, mixed>>
 */
function buildProductStockRiskSummary(array $productInsights): array
{
    if ($productInsights === []) {
        return [];
    }

    $insights = array_map(static function (array $info): array {
        $daysCover = $info['days_cover'] ?? null;
        $daysCover = is_numeric($daysCover) ? (float) $daysCover : null;
        $riskLevel = 'ok';
        if (!empty($info['below_threshold'])) {
            $riskLevel = 'warning';
            if ($daysCover !== null && $daysCover < 4) {
                $riskLevel = 'critical';
            }
        }

        return [
            'product_name' => (string) ($info['product_name'] ?? ''),
            'current_stock' => (int) ($info['current_stock'] ?? 0),
            'stock_reserved' => (int) ($info['stock_reserved'] ?? 0),
            'threshold' => (int) ($info['threshold'] ?? 0),
            'average_daily_sales' => (float) ($info['average_daily_sales'] ?? 0.0),
            'days_cover' => $daysCover,
            'suggested_reorder' => (int) ($info['suggested_reorder'] ?? 0),
            'risk_level' => $riskLevel,
        ];
    }, $productInsights);

    usort($insights, static function (array $a, array $b): int {
        $rank = ['critical' => 0, 'warning' => 1, 'ok' => 2];
        $riskA = $rank[$a['risk_level']] ?? 2;
        $riskB = $rank[$b['risk_level']] ?? 2;
        if ($riskA !== $riskB) {
            return $riskA <=> $riskB;
        }
        $coverA = $a['days_cover'] ?? PHP_FLOAT_MAX;
        $coverB = $b['days_cover'] ?? PHP_FLOAT_MAX;
        return $coverA <=> $coverB;
    });

    return array_slice($insights, 0, 5);
}

/**
 * @param array<string, mixed> $metrics
 * @param array<int, array<string, mixed>> $providerInsights
 * @param array<int, array<string, mixed>> $productInsights
 * @param array<string, mixed> $campaignPerformance
 * @param array<int, array<string, mixed>> $stockAlerts
 * @param array<int, array<string, mixed>> $productAlerts
 * @return array<int, array<string, string>>
 */
function buildDashboardNextSteps(
    array $metrics,
    array $providerInsights,
    array $productInsights,
    array $campaignPerformance,
    array $stockAlerts,
    array $productAlerts
): array {
    $steps = [];

    $lowStock = array_filter($providerInsights, static fn(array $info): bool => !empty($info['below_threshold']));
    if ($lowStock !== []) {
        $names = array_map(static fn(array $info): string => (string) ($info['provider_name'] ?? ''), $lowStock);
        $reasonBuckets = [];
        $maxSuggested = 0;
        foreach ($lowStock as $info) {
            if (!empty($info['suggested_reorder'])) {
                $qty = (int) $info['suggested_reorder'];
                if ($qty > $maxSuggested) {
                    $maxSuggested = $qty;
                }
            }

            $cover = $info['days_cover'] ?? null;
            if ($cover !== null && is_numeric($cover) && !isset($reasonBuckets['cover']) && (float) $cover < 2.5) {
                $reasonBuckets['cover'] = 'Copertura inferiore a 3 giorni: crea urgenza con bundle smartphone + SIM.';
            }

            $avg = $info['average_daily_sales'] ?? null;
            if ($avg !== null && is_numeric($avg)) {
                $avgFloat = (float) $avg;
                if ($avgFloat <= 0.05 && !isset($reasonBuckets['no_sales'])) {
                    $reasonBuckets['no_sales'] = 'Zero vendite negli ultimi giorni: coinvolgi il team con promo flash e contest.';
                } elseif ($avgFloat > 0.05 && $avgFloat <= 0.3 && !isset($reasonBuckets['slow_sales'])) {
                    $reasonBuckets['slow_sales'] = 'Flusso lento: combina attivazione + accessorio per riaccendere le vendite.';
                }
            }
        }

        if ($maxSuggested > 0) {
            $reasonBuckets = ['reorder' => 'Riordina almeno ' . $maxSuggested . ' SIM e rilancia mini-incentivi sugli upsell.'] + $reasonBuckets;
        }

        if ($reasonBuckets === []) {
            $reasonBuckets['default'] = 'Accendi il corner operatori con demo live e offerte lampo per spingere le attivazioni.';
        }

        $motivation = implode(' • ', array_slice(array_values($reasonBuckets), 0, 2));
        $steps[] = [
            'label' => 'Riordina SIM per: ' . implode(', ', array_slice($names, 0, 3)),
            'motivation' => $motivation,
            'severity' => 'warning',
        ];
    }

    $criticalCover = array_filter($providerInsights, static function (array $info): bool {
        $days = $info['days_cover'] ?? null;
        return !empty($info['below_threshold']) && $days !== null && is_numeric($days) && (float) $days < 3;
    });
    if ($criticalCover !== []) {
        $names = array_map(static fn(array $info): string => (string) ($info['provider_name'] ?? ''), $criticalCover);
        $steps[] = [
            'label' => 'Copertura sotto i 3 giorni per: ' . implode(', ', array_slice($names, 0, 3)),
            'motivation' => null,
            'severity' => 'critical',
        ];
    }

    $hardwareLowStock = array_filter($productInsights, static fn(array $info): bool => !empty($info['below_threshold']));
    if ($hardwareLowStock !== []) {
        $names = array_map(static fn(array $info): string => (string) ($info['product_name'] ?? ''), $hardwareLowStock);
        $maxSuggested = 0;
        foreach ($hardwareLowStock as $info) {
            $candidate = (int) ($info['suggested_reorder'] ?? 0);
            if ($candidate > $maxSuggested) {
                $maxSuggested = $candidate;
            }
        }

        $motivation = $maxSuggested > 0
            ? 'Pianifica ordine per almeno ' . $maxSuggested . ' pezzi e aggiorna la disponibilità online.'
            : 'Controlla prenotazioni e resi per riallineare lo stock hardware.';

        $steps[] = [
            'label' => 'Prodotti critici: ' . implode(', ', array_slice($names, 0, 3)),
            'motivation' => $motivation,
            'severity' => 'warning',
        ];
    }

    $campaignItems = $campaignPerformance['items'] ?? [];
    $activeCampaigns = array_filter($campaignItems, static fn(array $item): bool => !empty($item['is_active']));
    if ($activeCampaigns === []) {
        $steps[] = [
            'label' => 'Attiva una campagna sconto per stimolare le vendite.',
            'motivation' => null,
            'severity' => 'info',
        ];
    } else {
        $endingSoon = array_filter($activeCampaigns, static function (array $item): bool {
            $days = $item['ends_in_days'] ?? null;
            return $days !== null && is_numeric($days) && (int) $days >= 0 && (int) $days <= 3;
        });
        if ($endingSoon !== []) {
            $names = array_map(static fn(array $item): string => (string) ($item['name'] ?? ''), $endingSoon);
            $steps[] = [
                'label' => 'Campagne in scadenza breve: ' . implode(', ', array_slice($names, 0, 3)),
                'motivation' => null,
                'severity' => 'warning',
            ];
        }
    }

    if ($stockAlerts !== []) {
        $steps[] = [
            'label' => 'Gestisci ' . count($stockAlerts) . ' alert di stock aperti.',
            'motivation' => null,
            'severity' => 'warning',
        ];
    }

    if ($productAlerts !== []) {
        $steps[] = [
            'label' => 'Verifica ' . count($productAlerts) . ' alert hardware.',
            'motivation' => null,
            'severity' => 'warning',
        ];
    }

    $trend = $metrics['sales_trend']['points'] ?? [];
    if (count($trend) >= 2) {
        $last = end($trend);
        $prev = prev($trend);
        if ($last !== false && $prev !== false && ($last['count'] ?? 0) < ($prev['count'] ?? 0)) {
            $steps[] = [
                'label' => 'Vendite in calo rispetto al giorno precedente, valuta una promo mirata.',
                'motivation' => null,
                'severity' => 'info',
            ];
        }
        reset($trend);
    }

    if ($steps === []) {
        $steps[] = [
            'label' => 'Dashboard in ordine: continua a monitorare le performance.',
            'motivation' => null,
            'severity' => 'success',
        ];
    }

    return array_slice($steps, 0, 5);
}

function isAjaxRequest(): bool
{
    $requestedWith = $_SERVER['HTTP_X_REQUESTED_WITH'] ?? '';
    return strtolower((string) $requestedWith) === 'xmlhttprequest';
}

/**
 * @return array<mixed>
 */
function getJsonBody(): array
{
    static $cached = null;
    if ($cached !== null) {
        return $cached;
    }

    $rawBody = file_get_contents('php://input');
    if ($rawBody === false || $rawBody === '') {
        $cached = [];
        return $cached;
    }

    $decoded = json_decode($rawBody, true);
    $cached = is_array($decoded) ? $decoded : [];
    return $cached;
}

/**
 * @return array<string, mixed>|null
 */
function authenticateApi(): ?array
{
    global $authService;
    $authHeader = $_SERVER['HTTP_AUTHORIZATION'] ?? '';
    if (str_starts_with($authHeader, 'Bearer ')) {
        $token = substr($authHeader, 7);
        session_id($token);
        session_start();
        return $authService->currentUser();
    }
    return null;
}

/**
 * @param array<int, array<string, mixed>> $rows
 * @return array<int, array<string, mixed>>
 */
function formatSalesRowsForJson(array $rows): array
{
    return array_map(static function (array $sale): array {
        $customer = isset($sale['customer_name']) && $sale['customer_name'] !== null && $sale['customer_name'] !== ''
            ? (string) $sale['customer_name']
            : 'Cliente non specificato';
        $operator = isset($sale['fullname']) && $sale['fullname'] !== null && $sale['fullname'] !== ''
            ? (string) $sale['fullname']
            : (string) ($sale['username'] ?? 'Operatore');

        $statusConfig = match ($sale['status'] ?? null) {
            'Cancelled' => ['label' => 'Annullato', 'class' => 'badge--muted'],
            'Refunded' => ['label' => 'Reso', 'class' => 'badge--warning'],
            default => ['label' => 'Completato', 'class' => 'badge--success'],
        };

        try {
            $created = new DateTimeImmutable((string) ($sale['created_at'] ?? 'now'));
            $createdFormatted = $created->format('d/m/Y H:i');
        } catch (\Exception $exception) {
            $createdFormatted = (string) ($sale['created_at'] ?? '-');
        }

        $id = (int) ($sale['id'] ?? 0);

        return [
            'id' => $id,
            'created_at_display' => $createdFormatted,
            'customer_display' => $customer,
            'operator_display' => $operator,
            'payment_method' => (string) ($sale['payment_method'] ?? ''),
            'total_display' => '€ ' . number_format((float) ($sale['total'] ?? 0), 2, ',', '.'),
            'status_label' => $statusConfig['label'],
            'status_class' => $statusConfig['class'],
            'status_value' => (string) ($sale['status'] ?? ''),
            'print_url' => 'print_receipt.php?sale_id=' . $id,
        ];
    }, $rows);
}
