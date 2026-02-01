<?php
declare(strict_types=1);

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

use App\Services\NotificationDispatcher;
use App\Services\SystemNotificationService;
use App\Services\TenantContext;

$pdo = Database::getConnection();
$config = $GLOBALS['config'] ?? [];
$appConfig = $config['app'] ?? [];
$alertsConfig = $config['alerts'] ?? [];
$resendApiKey = $alertsConfig['resend_api_key'] ?? null;
$resendFrom = $alertsConfig['resend_from'] ?? 'alerts@coresuite.test';
$resendFromName = $alertsConfig['resend_from_name'] ?? null;
$logFile = __DIR__ . '/../storage/logs/license_renewals.log';

$portalUrl = $appConfig['portal_url'] ?? null;
if (!is_string($portalUrl) || trim($portalUrl) === '') {
    $portalUrl = null;
}

$notificationsConfig = $config['notifications'] ?? [];
$dispatcher = new NotificationDispatcher(
    $notificationsConfig['webhook_url'] ?? null,
    $notificationsConfig['webhook_headers'] ?? [],
    $notificationsConfig['queue'] ?? null,
    $logFile
);
$notificationService = new SystemNotificationService($pdo, $dispatcher, $logFile);

$log = static function (string $message) use ($logFile): void {
    $line = '[' . date('c') . '] ' . $message . PHP_EOL;
    file_put_contents($logFile, $line, FILE_APPEND);
};

$sendResendEmail = static function (string $to, string $subject, string $textBody, string $htmlBody) use ($resendApiKey, $resendFrom, $resendFromName, $log): bool {
    if ($resendApiKey === null || $resendApiKey === '') {
        return false;
    }

    if (!function_exists('curl_init')) {
        $log('Invio email fallito: cURL non disponibile.');
        return false;
    }

    $fromLabel = $resendFromName !== null && $resendFromName !== ''
        ? $resendFromName . ' <' . $resendFrom . '>'
        : $resendFrom;

    $payload = json_encode([
        'from' => $fromLabel,
        'to' => [$to],
        'subject' => $subject,
        'text' => $textBody,
        'html' => $htmlBody,
    ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

    if ($payload === false) {
        $log('Invio email fallito: payload non serializzabile.');
        return false;
    }

    $ch = curl_init('https://api.resend.com/emails');
    if ($ch === false) {
        $log('Invio email fallito: init cURL non riuscito.');
        return false;
    }

    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $payload);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Authorization: Bearer ' . $resendApiKey,
        'Content-Type: application/json',
    ]);
    curl_setopt($ch, CURLOPT_TIMEOUT, 10);

    $response = curl_exec($ch);
    $status = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    if ($response === false || $status < 200 || $status >= 300) {
        $log('Invio email fallito (Resend): ' . ($response === false ? 'nessuna risposta' : $response));
        return false;
    }

    return true;
};

$sendMailFallback = static function (string $to, string $subject, string $textBody) use ($resendFrom, $resendFromName): bool {
    $fromLabel = $resendFromName !== null && $resendFromName !== ''
        ? $resendFromName . ' <' . $resendFrom . '>'
        : $resendFrom;
    $headers = 'From: ' . $fromLabel;
    return @mail($to, $subject, $textBody, $headers);
};

$sql = "SELECT tl.id AS assignment_id,
               tl.tenant_id,
               t.name AS tenant_name,
               t.contact_email,
               l.code AS license_code,
               l.label AS license_label,
               l.expires_at
        FROM tenant_licenses tl
        INNER JOIN tenants t ON t.id = tl.tenant_id
        INNER JOIN licenses l ON l.id = tl.license_id
        WHERE tl.revoked_at IS NULL
          AND t.is_active = 1
                    AND l.is_active = 1
          AND l.expires_at IS NOT NULL
          AND tl.renewal_notice_sent_at IS NULL
          AND DATEDIFF(l.expires_at, CURDATE()) = 30";

$stmt = $pdo->query($sql);
$rows = $stmt !== false ? $stmt->fetchAll(PDO::FETCH_ASSOC) : [];

if ($rows === []) {
    echo "Nessuna licenza in scadenza a 30 giorni.\n";
    exit(0);
}

foreach ($rows as $row) {
    $assignmentId = (int) ($row['assignment_id'] ?? 0);
    $tenantId = (int) ($row['tenant_id'] ?? 0);
    $tenantName = (string) ($row['tenant_name'] ?? '');
    $email = trim((string) ($row['contact_email'] ?? ''));
    $licenseCode = (string) ($row['license_code'] ?? '');
    $licenseLabel = (string) ($row['license_label'] ?? '');
    $expiresAt = (string) ($row['expires_at'] ?? '');

    if ($assignmentId <= 0 || $tenantId <= 0 || $expiresAt === '') {
        continue;
    }

    $displayLabel = $licenseLabel !== '' ? $licenseLabel : $licenseCode;
    $subject = 'Licenza in scadenza tra 30 giorni';
    $textBody = "Ciao {$tenantName},\n\n";
    $textBody .= "La tua licenza {$displayLabel} scade il {$expiresAt}.\n";
    $textBody .= "Hai 30 giorni per rinnovare la licenza pagando la quota di adesione.\n\n";
    if ($portalUrl !== null) {
        $textBody .= "Accedi qui per rinnovare: {$portalUrl}\n\n";
    }
    $textBody .= "Per info sul rinnovo, contatta l'amministrazione.\n";

    $htmlBody = '<p>Ciao <strong>' . htmlspecialchars($tenantName) . '</strong>,</p>';
    $htmlBody .= '<p>La tua licenza <strong>' . htmlspecialchars($displayLabel) . '</strong> scade il <strong>' . htmlspecialchars($expiresAt) . '</strong>.</p>';
    $htmlBody .= '<p>Hai 30 giorni per rinnovare la licenza pagando la quota di adesione.</p>';
    if ($portalUrl !== null) {
        $htmlBody .= '<p><a href="' . htmlspecialchars($portalUrl) . '">Accedi per rinnovare</a>.</p>';
    }
    $htmlBody .= '<p>Per info sul rinnovo, contatta l&#39;amministrazione.</p>';

    $emailSent = false;
    if ($email !== '' && filter_var($email, FILTER_VALIDATE_EMAIL)) {
        if ($sendResendEmail($email, $subject, $textBody, $htmlBody)) {
            $emailSent = true;
        } elseif ($sendMailFallback($email, $subject, $textBody)) {
            $emailSent = true;
        }
    }

    TenantContext::setTenantId($tenantId);
    $notificationService->push(
        'license',
        'Licenza in scadenza',
        'La licenza ' . $displayLabel . ' scade il ' . $expiresAt . '. Hai 30 giorni per rinnovare.',
        [
            'level' => 'warning',
            'channel' => 'system',
            'link' => $portalUrl,
        ]
    );

    if ($emailSent) {
        $update = $pdo->prepare('UPDATE tenant_licenses SET renewal_notice_sent_at = NOW() WHERE id = :id');
        $update->execute([':id' => $assignmentId]);
        echo "Notifica inviata al tenant #{$tenantId} ({$email}).\n";
    } else {
        $log('Email non inviata per tenant #' . $tenantId . ' - contatto non valido o invio fallito.');
        echo "Email non inviata per tenant #{$tenantId}.\n";
    }
}
