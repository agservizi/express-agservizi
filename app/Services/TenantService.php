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
                    tl.max_users_override, tl.assigned_at, tl.revoked_at, tl.notes
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
}
