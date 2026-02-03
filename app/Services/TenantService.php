<?php
declare(strict_types=1);

namespace App\Services;

use PDO;

final class TenantService
{
    public function __construct(
        private PDO $pdo,
        private ?string $resendApiKey = null,
        private ?string $resendFrom = null,
        private ?string $resendFromName = null,
        private ?string $appName = null
    ) {
    }

    private function buildSlug(string $value): string
    {
        $slug = trim($value);
        if ($slug === '') {
            return '';
        }
        if (function_exists('iconv')) {
            $converted = @iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $slug);
            if ($converted !== false) {
                $slug = $converted;
            }
        }
        $slug = function_exists('mb_strtolower') ? mb_strtolower($slug, 'UTF-8') : strtolower($slug);
        $slug = preg_replace('/[^a-z0-9]+/i', '-', $slug) ?? '';
        $slug = trim($slug, '-');
        $slug = preg_replace('/-+/', '-', $slug) ?? '';

        return $slug;
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function listTenants(): array
    {
        $stmt = $this->pdo->query(
            'SELECT id, name, slug, contact_email, contact_phone, vat_number, company_country, company_name, company_address,
                is_active, created_at, updated_at
             FROM tenants
             ORDER BY created_at DESC'
        );

        return $stmt ? ($stmt->fetchAll(PDO::FETCH_ASSOC) ?: []) : [];
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function listTenantLicenses(): array
    {
        $stmt = $this->pdo->query(
            'SELECT tl.id, tl.tenant_id, t.name AS tenant_name, t.slug AS tenant_slug,
                tl.license_id, l.code AS license_code, l.label AS license_label,
                l.expires_at AS license_expires_at, l.term_months AS license_term_months,
                tl.max_users_override, tl.assigned_at, tl.revoked_at, tl.renewal_notice_sent_at,
                tl.renewal_paid_at, tl.notes
             FROM tenant_licenses tl
             INNER JOIN tenants t ON t.id = tl.tenant_id
             INNER JOIN licenses l ON l.id = tl.license_id
             ORDER BY tl.assigned_at DESC'
        );

        return $stmt ? ($stmt->fetchAll(PDO::FETCH_ASSOC) ?: []) : [];
    }

    /**
     * @param array<string, mixed> $input
     * @return array{success:bool,message:string,error?:string}
     */
    public function createTenant(array $input): array
    {
        $name = trim((string) ($input['tenant_name'] ?? ''));
        $slug = trim((string) ($input['tenant_slug'] ?? ''));
        if ($slug === '' && $name !== '') {
            $slug = $this->buildSlug($name);
        }
        $email = trim((string) ($input['tenant_email'] ?? ''));
        $phone = trim((string) ($input['tenant_phone'] ?? ''));
        $vatNumber = strtoupper(trim((string) ($input['vat_number'] ?? '')));
        $companyCountry = strtoupper(trim((string) ($input['company_country'] ?? '')));
        if ($companyCountry !== '' && strncmp($vatNumber, $companyCountry, strlen($companyCountry)) === 0) {
            $vatNumber = substr($vatNumber, strlen($companyCountry));
        }
        $companyName = trim((string) ($input['company_name'] ?? ''));
        $companyAddress = trim((string) ($input['company_address'] ?? ''));
        if ($vatNumber === '' && $companyName === '' && $companyAddress === '') {
            $companyCountry = '';
        }
        $skipWelcomeEmail = !empty($input['skip_welcome_email']);

        if ($name === '' || $slug === '') {
            return [
                'success' => false,
                'message' => 'Impossibile creare il tenant.',
                'error' => 'Nome e slug sono obbligatori.',
            ];
        }

        $stmt = $this->pdo->prepare(
            'INSERT INTO tenants (name, slug, contact_email, contact_phone, vat_number, company_country, company_name, company_address, is_active)
             VALUES (:name, :slug, :email, :phone, :vat_number, :company_country, :company_name, :company_address, 1)'
        );
        $stmt->execute([
            ':name' => $name,
            ':slug' => $slug,
            ':email' => $email !== '' ? $email : null,
            ':phone' => $phone !== '' ? $phone : null,
            ':vat_number' => $vatNumber !== '' ? $vatNumber : null,
            ':company_country' => $companyCountry !== '' ? $companyCountry : null,
            ':company_name' => $companyName !== '' ? $companyName : null,
            ':company_address' => $companyAddress !== '' ? $companyAddress : null,
        ]);

        $tenantId = (int) $this->pdo->lastInsertId();
        if ($tenantId > 0) {
            (new ReceiptSettingsService())->initializeForTenant($tenantId, $name);
        }

        $emailSent = false;
        if (!$skipWelcomeEmail && $email !== '' && filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $emailSent = $this->sendTenantWelcomeEmail($email, $name, $slug, $phone !== '' ? $phone : null);
        }

        return [
            'success' => true,
            'message' => $emailSent
                ? 'Tenant creato correttamente. Email inviata al contatto.'
                : 'Tenant creato correttamente.' . ($email !== '' && !$skipWelcomeEmail ? ' Email non inviata.' : ''),
            'email_sent' => $emailSent,
            'tenant_id' => $tenantId,
        ];
    }

    private function sendTenantWelcomeEmail(string $recipient, string $tenantName, string $tenantSlug, ?string $phone): bool
    {
        $appName = $this->appName ?: 'Gestionale';
        $subject = 'Tenant creato - ' . $appName;

        $lines = [
            'Il tenant è stato creato correttamente.',
            'Nome tenant: ' . $tenantName,
            'Slug tenant: ' . $tenantSlug,
        ];
        if ($phone !== null && $phone !== '') {
            $lines[] = 'Telefono: ' . $phone;
        }
        $lines[] = '';
        $lines[] = 'Per accedere, utilizza le credenziali fornite dall’amministratore.';

        $textBody = implode("\n", $lines);
        $htmlBody = '<p>' . implode('</p><p>', array_map('htmlspecialchars', $lines)) . '</p>';

        if ($this->resendApiKey !== null && $this->sendEmailViaResend($recipient, $subject, $textBody, $htmlBody)) {
            return true;
        }

        $headers = [];
        $fromEmail = trim((string) ($this->resendFrom ?? ''));
        if ($fromEmail !== '') {
            $fromName = trim((string) ($this->resendFromName ?? ''));
            $from = $fromName !== '' ? $fromName . ' <' . $fromEmail . '>' : $fromEmail;
            $headers[] = 'From: ' . $from;
        }

        if ($htmlBody !== null) {
            $headers[] = 'MIME-Version: 1.0';
            $headers[] = 'Content-Type: text/html; charset=UTF-8';
        }

        return @mail($recipient, $subject, $htmlBody ?? $textBody, implode("\r\n", $headers));
    }

    private function sendEmailViaResend(string $recipient, string $subject, string $textBody, ?string $htmlBody = null): bool
    {
        if (!function_exists('curl_init') || $this->resendApiKey === null) {
            return false;
        }

        $fromEmail = trim((string) ($this->resendFrom ?? ''));
        if ($fromEmail === '') {
            $fromEmail = 'support@coresuite.test';
        }
        $fromName = trim((string) ($this->resendFromName ?? ''));
        $from = $fromName !== '' ? $fromName . ' <' . $fromEmail . '>' : $fromEmail;

        $payload = json_encode([
            'from' => $from,
            'to' => [$recipient],
            'subject' => $subject,
            'text' => $textBody,
            'html' => $htmlBody,
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
                'Authorization: Bearer ' . $this->resendApiKey,
                'Content-Type: application/json',
            ],
            CURLOPT_TIMEOUT => 10,
        ]);

        $response = curl_exec($ch);
        $status = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        return $response !== false && $status >= 200 && $status < 300;
    }

    /**
     * @param array<string, mixed> $input
     * @return array{success:bool,message:string,error?:string}
     */
    public function updateTenant(int $tenantId, array $input): array
    {
        if ($tenantId <= 0) {
            return [
                'success' => false,
                'message' => 'Impossibile aggiornare il tenant.',
                'error' => 'Tenant non valido.',
            ];
        }

        $name = trim((string) ($input['tenant_name'] ?? ''));
        $slug = trim((string) ($input['tenant_slug'] ?? ''));
        if ($slug === '' && $name !== '') {
            $slug = $this->buildSlug($name);
        }
        $email = trim((string) ($input['tenant_email'] ?? ''));
        $phone = trim((string) ($input['tenant_phone'] ?? ''));
        $vatNumber = strtoupper(trim((string) ($input['vat_number'] ?? '')));
        $companyCountry = strtoupper(trim((string) ($input['company_country'] ?? '')));
        if ($companyCountry !== '' && strncmp($vatNumber, $companyCountry, strlen($companyCountry)) === 0) {
            $vatNumber = substr($vatNumber, strlen($companyCountry));
        }
        $companyName = trim((string) ($input['company_name'] ?? ''));
        $companyAddress = trim((string) ($input['company_address'] ?? ''));
        if ($vatNumber === '' && $companyName === '' && $companyAddress === '') {
            $companyCountry = '';
        }

        if ($name === '' || $slug === '') {
            return [
                'success' => false,
                'message' => 'Impossibile aggiornare il tenant.',
                'error' => 'Nome e slug sono obbligatori.',
            ];
        }

        $stmt = $this->pdo->prepare(
            'UPDATE tenants
             SET name = :name,
                 slug = :slug,
                 contact_email = :email,
                 contact_phone = :phone,
                 vat_number = :vat_number,
                 company_country = :company_country,
                 company_name = :company_name,
                 company_address = :company_address
             WHERE id = :id'
        );
        $stmt->execute([
            ':name' => $name,
            ':slug' => $slug,
            ':email' => $email !== '' ? $email : null,
            ':phone' => $phone !== '' ? $phone : null,
            ':vat_number' => $vatNumber !== '' ? $vatNumber : null,
            ':company_country' => $companyCountry !== '' ? $companyCountry : null,
            ':company_name' => $companyName !== '' ? $companyName : null,
            ':company_address' => $companyAddress !== '' ? $companyAddress : null,
            ':id' => $tenantId,
        ]);

        return [
            'success' => true,
            'message' => 'Tenant aggiornato correttamente.',
        ];
    }

    /**
     * @return array{success:bool,message:string,error?:string}
     */
    public function toggleTenant(int $tenantId, bool $enabled): array
    {
        if ($tenantId <= 0) {
            return [
                'success' => false,
                'message' => 'Impossibile aggiornare il tenant.',
                'error' => 'Tenant non valido.',
            ];
        }

        $stmt = $this->pdo->prepare('UPDATE tenants SET is_active = :active WHERE id = :id');
        $stmt->execute([
            ':active' => $enabled ? 1 : 0,
            ':id' => $tenantId,
        ]);

        return [
            'success' => true,
            'message' => $enabled ? 'Tenant attivato.' : 'Tenant disattivato.',
        ];
    }

    /**
     * @param array<string, mixed> $input
     * @return array{success:bool,message:string,error?:string}
     */
    public function assignLicense(array $input): array
    {
        $tenantId = isset($input['tenant_id']) ? (int) $input['tenant_id'] : 0;
        $licenseId = isset($input['license_id']) ? (int) $input['license_id'] : 0;
        $maxUsers = isset($input['max_users_override']) && $input['max_users_override'] !== ''
            ? (int) $input['max_users_override']
            : null;
        $notes = trim((string) ($input['assignment_notes'] ?? ''));

        if ($tenantId <= 0 || $licenseId <= 0) {
            return [
                'success' => false,
                'message' => 'Impossibile assegnare la licenza.',
                'error' => 'Seleziona tenant e licenza validi.',
            ];
        }

        $existsStmt = $this->pdo->prepare(
            'SELECT id FROM tenant_licenses
             WHERE tenant_id = :tenant AND license_id = :license AND revoked_at IS NULL
             LIMIT 1'
        );
        $existsStmt->execute([
            ':tenant' => $tenantId,
            ':license' => $licenseId,
        ]);
        if ($existsStmt->fetchColumn()) {
            return [
                'success' => false,
                'message' => 'Impossibile assegnare la licenza.',
                'error' => 'Esiste già un\'assegnazione attiva per questo tenant.',
            ];
        }

        $stmt = $this->pdo->prepare(
            'INSERT INTO tenant_licenses (tenant_id, license_id, max_users_override, notes)
             VALUES (:tenant, :license, :max_users, :notes)'
        );
        $stmt->execute([
            ':tenant' => $tenantId,
            ':license' => $licenseId,
            ':max_users' => $maxUsers,
            ':notes' => $notes !== '' ? $notes : null,
        ]);

        $assignmentId = (int) $this->pdo->lastInsertId();

        return [
            'success' => true,
            'message' => 'Licenza assegnata al tenant.',
            'assignment_id' => $assignmentId,
        ];
    }

    /**
     * @return array{success:bool,message:string,error?:string}
     */
    public function revokeTenantLicense(int $assignmentId): array
    {
        if ($assignmentId <= 0) {
            return [
                'success' => false,
                'message' => 'Impossibile revocare l\'assegnazione.',
                'error' => 'Assegnazione non valida.',
            ];
        }

        $stmt = $this->pdo->prepare('UPDATE tenant_licenses SET revoked_at = NOW() WHERE id = :id AND revoked_at IS NULL');
        $stmt->execute([':id' => $assignmentId]);

        return [
            'success' => true,
            'message' => 'Assegnazione revocata.',
        ];
    }

    /**
     * @return array{success:bool,message:string,error?:string,expires_at?:string}
     */
    public function renewTenantLicense(int $assignmentId): array
    {
        if ($assignmentId <= 0) {
            return [
                'success' => false,
                'message' => 'Impossibile rinnovare la licenza.',
                'error' => 'Assegnazione non valida.',
            ];
        }

        $stmt = $this->pdo->prepare(
            'SELECT tl.id, tl.revoked_at, tl.license_id, l.expires_at, l.term_months, l.is_active
             FROM tenant_licenses tl
             INNER JOIN licenses l ON l.id = tl.license_id
             WHERE tl.id = :id
             LIMIT 1'
        );
        $stmt->execute([':id' => $assignmentId]);
        $assignment = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($assignment === false) {
            return [
                'success' => false,
                'message' => 'Impossibile rinnovare la licenza.',
                'error' => 'Assegnazione non trovata.',
            ];
        }

        if (!empty($assignment['revoked_at'])) {
            return [
                'success' => false,
                'message' => 'Impossibile rinnovare la licenza.',
                'error' => 'Assegnazione revocata.',
            ];
        }

        if ((int) ($assignment['is_active'] ?? 0) !== 1) {
            return [
                'success' => false,
                'message' => 'Impossibile rinnovare la licenza.',
                'error' => 'Licenza non attiva.',
            ];
        }

        $termMonths = (int) ($assignment['term_months'] ?? 0);
        if (!in_array($termMonths, [12, 24, 36], true)) {
            return [
                'success' => false,
                'message' => 'Impossibile rinnovare la licenza.',
                'error' => 'Durata licenza non valida.',
            ];
        }

        $baseDate = new \DateTimeImmutable('now');
        if (!empty($assignment['expires_at'])) {
            try {
                $expiresAt = new \DateTimeImmutable((string) $assignment['expires_at']);
                if ($expiresAt > $baseDate) {
                    $baseDate = $expiresAt;
                }
            } catch (\Throwable) {
                // usa now
            }
        }

        $newExpiry = $baseDate->modify('+' . $termMonths . ' months')->format('Y-m-d');

        $updateLicense = $this->pdo->prepare('UPDATE licenses SET expires_at = :expires WHERE id = :id');
        $updateLicense->execute([
            ':expires' => $newExpiry,
            ':id' => (int) $assignment['license_id'],
        ]);

        $updateAssignment = $this->pdo->prepare(
            'UPDATE tenant_licenses
             SET renewal_paid_at = NOW(), renewal_notice_sent_at = NULL
             WHERE id = :id'
        );
        $updateAssignment->execute([':id' => $assignmentId]);

        return [
            'success' => true,
            'message' => 'Licenza rinnovata correttamente.',
            'expires_at' => $newExpiry,
        ];
    }

    /**
     * @return array{success:bool,message:string,error?:string}
     */
    public function updateAssignmentPayment(int $assignmentId, bool $paid): array
    {
        if ($assignmentId <= 0) {
            return [
                'success' => false,
                'message' => 'Impossibile aggiornare la quota di adesione.',
                'error' => 'Assegnazione non valida.',
            ];
        }

        $stmt = $this->pdo->prepare('SELECT id FROM tenant_licenses WHERE id = :id');
        $stmt->execute([':id' => $assignmentId]);
        if (!$stmt->fetchColumn()) {
            return [
                'success' => false,
                'message' => 'Impossibile aggiornare la quota di adesione.',
                'error' => 'Assegnazione non trovata.',
            ];
        }

        if ($paid) {
            $update = $this->pdo->prepare(
                'UPDATE tenant_licenses SET renewal_paid_at = NOW(), renewal_notice_sent_at = NULL WHERE id = :id'
            );
        } else {
            $update = $this->pdo->prepare(
                'UPDATE tenant_licenses SET renewal_paid_at = NULL WHERE id = :id'
            );
        }

        $update->execute([':id' => $assignmentId]);

        return [
            'success' => true,
            'message' => $paid ? 'Quota adesione aggiornata a Pagata.' : 'Quota adesione aggiornata a Da pagare.',
        ];
    }
}
