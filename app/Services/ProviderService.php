<?php
declare(strict_types=1);

namespace App\Services;

use PDO;
use App\Services\TenantContext;

final class ProviderService
{
    public function __construct(private PDO $pdo)
    {
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function listProviders(): array
    {
        $tenantId = TenantContext::id();
        $stmt = $this->pdo->query(
            'SELECT id, name, reorder_threshold, notes, created_at
             FROM providers
             WHERE tenant_id = ' . (int) $tenantId . '
             ORDER BY name'
        );
        return $stmt->fetchAll();
    }

    /**
     * @param array<string, mixed> $data
     * @return array{success:bool, message:string, errors?:array<int, string>}
     */
    public function createProvider(array $data, ?int $userId = null): array
    {
        $validation = $this->validate($data);
        if ($validation['errors'] !== []) {
            return [
                'success' => false,
                'message' => 'Impossibile creare il gestore.',
                'errors' => $validation['errors'],
            ];
        }

        $tenantId = TenantContext::id();
        $stmt = $this->pdo->prepare(
            'INSERT INTO providers (tenant_id, name, reorder_threshold, notes)
             VALUES (:tenant_id, :name, :threshold, :notes)'
        );
        $stmt->execute([
            ':tenant_id' => $tenantId,
            ':name' => $validation['name'],
            ':threshold' => $validation['reorder_threshold'],
            ':notes' => $validation['notes'],
        ]);

        $this->logAudit($userId, 'provider_create', 'Creato gestore: ' . $validation['name']);

        return [
            'success' => true,
            'message' => 'Gestore creato con successo.',
        ];
    }

    /**
     * @param array<string, mixed> $data
     * @return array{success:bool, message:string, errors?:array<int, string>}
     */
    public function updateProvider(int $id, array $data, ?int $userId = null): array
    {
        if ($id <= 0) {
            return [
                'success' => false,
                'message' => 'Gestore non valido.',
                'errors' => ['Seleziona un gestore valido da aggiornare.'],
            ];
        }

        $existing = $this->findProvider($id);
        if ($existing === null) {
            return [
                'success' => false,
                'message' => 'Gestore non trovato.',
                'errors' => ['Il gestore selezionato non esiste più.'],
            ];
        }

        $validation = $this->validate($data, $id);
        if ($validation['errors'] !== []) {
            return [
                'success' => false,
                'message' => 'Impossibile aggiornare il gestore.',
                'errors' => $validation['errors'],
            ];
        }

        $tenantId = TenantContext::id();
        $stmt = $this->pdo->prepare(
            'UPDATE providers
             SET name = :name, reorder_threshold = :threshold, notes = :notes
             WHERE id = :id AND tenant_id = :tenant_id'
        );
        $stmt->execute([
            ':name' => $validation['name'],
            ':threshold' => $validation['reorder_threshold'],
            ':notes' => $validation['notes'],
            ':id' => $id,
            ':tenant_id' => $tenantId,
        ]);

        $this->logAudit(
            $userId,
            'provider_update',
            'Aggiornato gestore #' . $id . ' (' . $existing['name'] . ' → ' . $validation['name'] . ')'
        );

        return [
            'success' => true,
            'message' => 'Gestore aggiornato con successo.',
        ];
    }

    public function deleteProvider(int $id, ?int $userId = null): array
    {
        if ($id <= 0) {
            return [
                'success' => false,
                'message' => 'Gestore non valido.',
                'errors' => ['Seleziona un gestore valido da eliminare.'],
            ];
        }

        $existing = $this->findProvider($id);
        if ($existing === null) {
            return [
                'success' => false,
                'message' => 'Gestore non trovato.',
                'errors' => ['Il gestore selezionato non esiste più.'],
            ];
        }

        $blocking = $this->countProviderDependencies($id);
        if ($blocking > 0) {
            return [
                'success' => false,
                'message' => 'Gestore non eliminabile.',
                'errors' => ['Esistono SIM, offerte o alert collegati a questo gestore.'],
            ];
        }

        $tenantId = TenantContext::id();
        $stmt = $this->pdo->prepare('DELETE FROM providers WHERE id = :id AND tenant_id = :tenant_id');
        $stmt->execute([':id' => $id, ':tenant_id' => $tenantId]);

        $this->logAudit($userId, 'provider_delete', 'Eliminato gestore #' . $id . ' (' . $existing['name'] . ')');

        return [
            'success' => true,
            'message' => 'Gestore eliminato con successo.',
        ];
    }

    /**
     * @return array<string, mixed>|null
     */
    private function findProvider(int $id): ?array
    {
        $tenantId = TenantContext::id();
        $stmt = $this->pdo->prepare('SELECT id, name FROM providers WHERE id = :id AND tenant_id = :tenant_id');
        $stmt->execute([':id' => $id, ':tenant_id' => $tenantId]);
        $row = $stmt->fetch();
        return $row === false ? null : $row;
    }

    /**
     * @param array<string, mixed> $data
     * @return array{errors:array<int, string>, name:string, reorder_threshold:int, notes:?string}
     */
    private function validate(array $data, ?int $currentId = null): array
    {
        $errors = [];
        $name = trim((string) ($data['provider_name'] ?? $data['name'] ?? ''));
        if ($name === '') {
            $errors[] = 'Il nome del gestore è obbligatorio.';
        }

        $threshold = isset($data['reorder_threshold']) ? (int) $data['reorder_threshold'] : 0;
        if ($threshold < 0) {
            $errors[] = 'La soglia deve essere maggiore o uguale a zero.';
            $threshold = 0;
        }

        $notes = trim((string) ($data['provider_notes'] ?? $data['notes'] ?? ''));
        if ($notes === '') {
            $notes = null;
        }

        if ($name !== '') {
            $tenantId = TenantContext::id();
            $stmt = $this->pdo->prepare(
                'SELECT id FROM providers WHERE LOWER(name) = LOWER(:name) AND tenant_id = :tenant_id'
                . ($currentId !== null ? ' AND id <> :id' : '')
            );
            $params = [':name' => $name];
            $params[':tenant_id'] = $tenantId;
            if ($currentId !== null) {
                $params[':id'] = $currentId;
            }
            $stmt->execute($params);
            if ($stmt->fetchColumn() !== false) {
                $errors[] = 'Esiste già un gestore con questo nome.';
            }
        }

        return [
            'errors' => $errors,
            'name' => $name,
            'reorder_threshold' => $threshold,
            'notes' => $notes,
        ];
    }

    private function countProviderDependencies(int $providerId): int
    {
        $tenantId = TenantContext::id();
        $tables = [
            'iccid_stock',
            'operator_offers',
            'stock_alerts',
        ];

        $total = 0;
        foreach ($tables as $table) {
            $stmt = $this->pdo->prepare(
                'SELECT COUNT(*) FROM ' . $table . ' WHERE provider_id = :id AND tenant_id = :tenant_id'
            );
            $stmt->execute([':id' => $providerId, ':tenant_id' => $tenantId]);
            $total += (int) $stmt->fetchColumn();
        }

        return $total;
    }

    private function logAudit(?int $userId, string $action, string $description): void
    {
        $userId = $userId !== null && $userId > 0 ? $userId : null;
        $tenantId = TenantContext::id();
        $stmt = $this->pdo->prepare(
            'INSERT INTO audit_log (tenant_id, user_id, action, description)
             VALUES (:tenant_id, :user_id, :action, :description)'
        );
        $stmt->execute([
            ':tenant_id' => $tenantId,
            ':user_id' => $userId,
            ':action' => $action,
            ':description' => $description,
        ]);
    }
}
