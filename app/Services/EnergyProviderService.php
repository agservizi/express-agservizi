<?php
declare(strict_types=1);

namespace App\Services;

use PDO;

final class EnergyProviderService
{
    public function __construct(private PDO $pdo)
    {
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function listProviders(): array
    {
        $stmt = $this->pdo->query(
            'SELECT id, name, service_type, token_luce, token_gas, notes, created_at, updated_at
             FROM energy_providers
             ORDER BY name'
        );

        return $stmt !== false ? $stmt->fetchAll(PDO::FETCH_ASSOC) : [];
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
                'message' => 'Impossibile creare il gestore energia.',
                'errors' => $validation['errors'],
            ];
        }

        $stmt = $this->pdo->prepare(
            'INSERT INTO energy_providers (name, service_type, token_luce, token_gas, notes)
             VALUES (:name, :service_type, :token_luce, :token_gas, :notes)'
        );
        $stmt->execute([
            ':name' => $validation['name'],
            ':service_type' => $validation['service_type'],
            ':token_luce' => $validation['token_luce'],
            ':token_gas' => $validation['token_gas'],
            ':notes' => $validation['notes'],
        ]);

        $this->logAudit($userId, 'energy_provider_create', 'Creato gestore energia: ' . $validation['name']);

        return [
            'success' => true,
            'message' => 'Gestore energia creato con successo.',
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
                'message' => 'Gestore energia non valido.',
                'errors' => ['Seleziona un gestore valido da aggiornare.'],
            ];
        }

        $existing = $this->findProvider($id);
        if ($existing === null) {
            return [
                'success' => false,
                'message' => 'Gestore energia non trovato.',
                'errors' => ['Il gestore selezionato non esiste più.'],
            ];
        }

        $validation = $this->validate($data, $id);
        if ($validation['errors'] !== []) {
            return [
                'success' => false,
                'message' => 'Impossibile aggiornare il gestore energia.',
                'errors' => $validation['errors'],
            ];
        }

        $stmt = $this->pdo->prepare(
            'UPDATE energy_providers
             SET name = :name, service_type = :service_type, token_luce = :token_luce, token_gas = :token_gas, notes = :notes
             WHERE id = :id'
        );
        $stmt->execute([
            ':name' => $validation['name'],
            ':service_type' => $validation['service_type'],
            ':token_luce' => $validation['token_luce'],
            ':token_gas' => $validation['token_gas'],
            ':notes' => $validation['notes'],
            ':id' => $id,
        ]);

        $this->logAudit(
            $userId,
            'energy_provider_update',
            'Aggiornato gestore energia #' . $id . ' (' . $existing['name'] . ' → ' . $validation['name'] . ')'
        );

        return [
            'success' => true,
            'message' => 'Gestore energia aggiornato con successo.',
        ];
    }

    /**
     * @return array{success:bool, message:string, errors?:array<int, string>}
     */
    public function deleteProvider(int $id, ?int $userId = null): array
    {
        if ($id <= 0) {
            return [
                'success' => false,
                'message' => 'Gestore energia non valido.',
                'errors' => ['Seleziona un gestore valido da eliminare.'],
            ];
        }

        $existing = $this->findProvider($id);
        if ($existing === null) {
            return [
                'success' => false,
                'message' => 'Gestore energia non trovato.',
                'errors' => ['Il gestore selezionato non esiste più.'],
            ];
        }

        $blocking = $this->countDependencies($id);
        if ($blocking > 0) {
            return [
                'success' => false,
                'message' => 'Gestore energia non eliminabile.',
                'errors' => ['Esistono contratti collegati a questo gestore.'],
            ];
        }

        $stmt = $this->pdo->prepare('DELETE FROM energy_providers WHERE id = :id');
        $stmt->execute([':id' => $id]);

        $this->logAudit($userId, 'energy_provider_delete', 'Eliminato gestore energia #' . $id . ' (' . $existing['name'] . ')');

        return [
            'success' => true,
            'message' => 'Gestore energia eliminato con successo.',
        ];
    }

    public function findProvider(int $id): ?array
    {
        $stmt = $this->pdo->prepare(
            'SELECT id, name, service_type, token_luce, token_gas
             FROM energy_providers WHERE id = :id'
        );
        $stmt->execute([':id' => $id]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row === false ? null : $row;
    }

    /**
     * @param array<string, mixed> $data
     * @return array{errors:array<int, string>, name:string, service_type:string, token_luce:float, token_gas:float, notes:?string}
     */
    private function validate(array $data, ?int $currentId = null): array
    {
        $errors = [];
        $name = trim((string) ($data['energy_provider_name'] ?? $data['name'] ?? ''));
        if ($name === '') {
            $errors[] = 'Il nome del gestore è obbligatorio.';
        }

        $serviceType = (string) ($data['energy_provider_type'] ?? $data['service_type'] ?? 'luce_gas');
        if (!in_array($serviceType, ['luce', 'gas', 'luce_gas'], true)) {
            $errors[] = 'Seleziona una tipologia valida (luce, gas o luce+gas).';
            $serviceType = 'luce_gas';
        }

        $tokenLuce = isset($data['energy_token_luce']) ? (float) $data['energy_token_luce'] : (float) ($data['token_luce'] ?? 0);
        $tokenGas = isset($data['energy_token_gas']) ? (float) $data['energy_token_gas'] : (float) ($data['token_gas'] ?? 0);
        if ($tokenLuce < 0) {
            $errors[] = 'Il gettone luce deve essere maggiore o uguale a zero.';
            $tokenLuce = 0.0;
        }
        if ($tokenGas < 0) {
            $errors[] = 'Il gettone gas deve essere maggiore o uguale a zero.';
            $tokenGas = 0.0;
        }

        $notes = trim((string) ($data['energy_provider_notes'] ?? $data['notes'] ?? ''));
        if ($notes === '') {
            $notes = null;
        }

        if ($name !== '') {
            $stmt = $this->pdo->prepare(
                'SELECT id FROM energy_providers WHERE LOWER(name) = LOWER(:name)'
                . ($currentId !== null ? ' AND id <> :id' : '')
            );
            $params = [':name' => $name];
            if ($currentId !== null) {
                $params[':id'] = $currentId;
            }
            $stmt->execute($params);
            if ($stmt->fetchColumn() !== false) {
                $errors[] = 'Esiste già un gestore energia con questo nome.';
            }
        }

        return [
            'errors' => $errors,
            'name' => $name,
            'service_type' => $serviceType,
            'token_luce' => $tokenLuce,
            'token_gas' => $tokenGas,
            'notes' => $notes,
        ];
    }

    private function countDependencies(int $providerId): int
    {
        $stmt = $this->pdo->prepare('SELECT COUNT(*) FROM energy_contracts WHERE provider_id = :id');
        $stmt->execute([':id' => $providerId]);
        return (int) $stmt->fetchColumn();
    }

    private function logAudit(?int $userId, string $action, string $description): void
    {
        $userId = $userId !== null && $userId > 0 ? $userId : null;
        $stmt = $this->pdo->prepare(
            'INSERT INTO audit_log (user_id, action, description) VALUES (:user_id, :action, :description)'
        );
        $stmt->execute([
            ':user_id' => $userId,
            ':action' => $action,
            ':description' => $description,
        ]);
    }
}
