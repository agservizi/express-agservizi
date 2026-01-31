<?php
declare(strict_types=1);

namespace App\Services;

use PDO;

final class TenantService
{
    public function __construct(private PDO $pdo)
    {
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function listTenants(): array
    {
        $stmt = $this->pdo->query(
            'SELECT id, name, slug, contact_email, contact_phone, is_active, created_at, updated_at
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
        $email = trim((string) ($input['tenant_email'] ?? ''));
        $phone = trim((string) ($input['tenant_phone'] ?? ''));

        if ($name === '' || $slug === '') {
            return [
                'success' => false,
                'message' => 'Impossibile creare il tenant.',
                'error' => 'Nome e slug sono obbligatori.',
            ];
        }

        $stmt = $this->pdo->prepare(
            'INSERT INTO tenants (name, slug, contact_email, contact_phone, is_active)
             VALUES (:name, :slug, :email, :phone, 1)'
        );
        $stmt->execute([
            ':name' => $name,
            ':slug' => $slug,
            ':email' => $email !== '' ? $email : null,
            ':phone' => $phone !== '' ? $phone : null,
        ]);

        return [
            'success' => true,
            'message' => 'Tenant creato correttamente.',
        ];
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
        $email = trim((string) ($input['tenant_email'] ?? ''));
        $phone = trim((string) ($input['tenant_phone'] ?? ''));

        if ($name === '' || $slug === '') {
            return [
                'success' => false,
                'message' => 'Impossibile aggiornare il tenant.',
                'error' => 'Nome e slug sono obbligatori.',
            ];
        }

        $stmt = $this->pdo->prepare(
            'UPDATE tenants
             SET name = :name, slug = :slug, contact_email = :email, contact_phone = :phone
             WHERE id = :id'
        );
        $stmt->execute([
            ':name' => $name,
            ':slug' => $slug,
            ':email' => $email !== '' ? $email : null,
            ':phone' => $phone !== '' ? $phone : null,
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

        return [
            'success' => true,
            'message' => 'Licenza assegnata al tenant.',
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
}
