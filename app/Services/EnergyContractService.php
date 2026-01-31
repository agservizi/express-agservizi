<?php
declare(strict_types=1);

namespace App\Services;

use DateTimeImmutable;
use PDO;
use App\Services\TenantContext;

final class EnergyContractService
{
    public function __construct(private PDO $pdo)
    {
    }

    /**
     * @param array<string, mixed> $data
     * @return array{success:bool, message:string, errors?:array<int, string>}
     */
    public function createContract(array $data, ?int $userId = null): array
    {
        $tenantId = TenantContext::id();
        $validation = $this->validate($data);
        if ($validation['errors'] !== []) {
            return [
                'success' => false,
                'message' => 'Impossibile registrare il contratto.',
                'errors' => $validation['errors'],
            ];
        }

        $provider = $this->findProvider($validation['provider_id']);
        if ($provider === null) {
            return [
                'success' => false,
                'message' => 'Gestore energia non valido.',
                'errors' => ['Seleziona un gestore valido.'],
            ];
        }

        if (!$this->isProviderCompatible($validation['contract_type'], (string) ($provider['service_type'] ?? ''))) {
            return [
                'success' => false,
                'message' => 'Tipologia contratto non compatibile con il gestore selezionato.',
                'errors' => ['Aggiorna la tipologia contratto o seleziona un altro gestore.'],
            ];
        }

        $tokenValue = $this->resolveTokenValue($validation['contract_type'], $provider);
        $customerId = $this->ensureCustomer($validation['customer_name']);

        $stmt = $this->pdo->prepare(
            'INSERT INTO energy_contracts (tenant_id, customer_id, customer_name, contract_type, provider_id, token_value, user_id, notes)
             VALUES (:tenant_id, :customer_id, :customer_name, :contract_type, :provider_id, :token_value, :user_id, :notes)'
        );
        $stmt->execute([
            ':tenant_id' => $tenantId,
            ':customer_id' => $customerId,
            ':customer_name' => $validation['customer_name'],
            ':contract_type' => $validation['contract_type'],
            ':provider_id' => $validation['provider_id'],
            ':token_value' => $tokenValue,
            ':user_id' => $userId !== null && $userId > 0 ? $userId : null,
            ':notes' => $validation['notes'],
        ]);

        return [
            'success' => true,
            'message' => 'Contratto energia registrato. Provvigione: € ' . number_format($tokenValue, 2, ',', '.'),
        ];
    }

    /**
     * @return array{rows: array<int, array<string, mixed>>, total: float, period_label: string, range: array{start:string,end:string}}
     */
    public function listByPeriod(string $period = 'month', ?string $date = null): array
    {
        $tenantId = TenantContext::id();
        $bounds = $this->resolvePeriodBounds($period, $date);
        $stmt = $this->pdo->prepare(
            'SELECT ec.id, ec.customer_name, ec.contract_type, ec.token_value, ec.created_at,
                    ec.notes, ep.name AS provider_name, ep.service_type
             FROM energy_contracts ec
             INNER JOIN energy_providers ep ON ep.id = ec.provider_id
             WHERE ec.tenant_id = :tenant_id AND ec.created_at BETWEEN :start AND :end
             ORDER BY ec.created_at DESC'
        );
        $stmt->execute([
            ':tenant_id' => $tenantId,
            ':start' => $bounds['start']->format('Y-m-d H:i:s'),
            ':end' => $bounds['end']->format('Y-m-d H:i:s'),
        ]);
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];

        $total = 0.0;
        foreach ($rows as $row) {
            $total += (float) ($row['token_value'] ?? 0.0);
        }

        return [
            'rows' => $rows,
            'total' => $total,
            'period_label' => $bounds['label'],
            'range' => [
                'start' => $bounds['start']->format('Y-m-d'),
                'end' => $bounds['end']->format('Y-m-d'),
            ],
        ];
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function searchContracts(string $term, int $limit = 5): array
    {
        $tenantId = TenantContext::id();
        $term = trim($term);
        if ($term === '') {
            return [];
        }

        $limit = max(1, min($limit, 10));

        $wildcard = '%' . $term . '%';
        $conditions = [
            'ec.customer_name LIKE :term_customer',
            'ep.name LIKE :term_provider',
            'ec.contract_type LIKE :term_contract',
            'ec.notes LIKE :term_notes',
        ];
        $params = [
            ':term_customer' => $wildcard,
            ':term_provider' => $wildcard,
            ':term_contract' => $wildcard,
            ':term_notes' => $wildcard,
        ];

        if (ctype_digit($term)) {
            $conditions[] = 'ec.id = :id';
            $params[':id'] = (int) $term;
        }

        $sql = 'SELECT ec.id, ec.customer_name, ec.contract_type, ec.token_value, ec.created_at,
                       ep.name AS provider_name, ep.service_type
                FROM energy_contracts ec
                INNER JOIN energy_providers ep ON ep.id = ec.provider_id
            WHERE ec.tenant_id = :tenant_id AND (' . implode(' OR ', $conditions) . ')
                ORDER BY ec.created_at DESC
                LIMIT :limit';

        $stmt = $this->pdo->prepare($sql);
        $stmt->bindValue(':tenant_id', $tenantId, PDO::PARAM_INT);
        foreach ($params as $key => $value) {
            $stmt->bindValue($key, $value);
        }
        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->execute();

        return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
    }

    /**
     * @return array{success:bool, message:string, errors?:array<int, string>}
     */
    public function deleteContract(int $contractId): array
    {
        $tenantId = TenantContext::id();
        if ($contractId <= 0) {
            return [
                'success' => false,
                'message' => 'Contratto non valido.',
                'errors' => ['Seleziona un contratto valido da eliminare.'],
            ];
        }

        $stmt = $this->pdo->prepare('SELECT id FROM energy_contracts WHERE id = :id AND tenant_id = :tenant_id');
        $stmt->execute([':id' => $contractId, ':tenant_id' => $tenantId]);
        if ($stmt->fetchColumn() === false) {
            return [
                'success' => false,
                'message' => 'Contratto non trovato.',
                'errors' => ['Il contratto selezionato non esiste più.'],
            ];
        }

        $delete = $this->pdo->prepare('DELETE FROM energy_contracts WHERE id = :id AND tenant_id = :tenant_id');
        $delete->execute([':id' => $contractId, ':tenant_id' => $tenantId]);

        return [
            'success' => true,
            'message' => 'Contratto eliminato con successo.',
        ];
    }

    private function resolveTokenValue(string $contractType, array $provider): float
    {
        $tokenLuce = (float) ($provider['token_luce'] ?? 0);
        $tokenGas = (float) ($provider['token_gas'] ?? 0);
        return match ($contractType) {
            'luce' => $tokenLuce,
            'gas' => $tokenGas,
            default => $tokenLuce + $tokenGas,
        };
    }

    private function isProviderCompatible(string $contractType, string $providerType): bool
    {
        if ($providerType === '') {
            return true;
        }

        if ($contractType === 'luce_gas') {
            return $providerType === 'luce_gas';
        }

        return $providerType === 'luce_gas' || $providerType === $contractType;
    }

    private function findProvider(int $providerId): ?array
    {
        $tenantId = TenantContext::id();
        $stmt = $this->pdo->prepare(
            'SELECT id, name, service_type, token_luce, token_gas
             FROM energy_providers WHERE id = :id AND tenant_id = :tenant_id'
        );
        $stmt->execute([':id' => $providerId, ':tenant_id' => $tenantId]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row === false ? null : $row;
    }

    private function ensureCustomer(string $customerName): ?int
    {
        $tenantId = TenantContext::id();
        $stmt = $this->pdo->prepare('SELECT id FROM customers WHERE tenant_id = :tenant_id AND LOWER(fullname) = LOWER(:name)');
        $stmt->execute([':tenant_id' => $tenantId, ':name' => $customerName]);
        $existingId = $stmt->fetchColumn();
        if ($existingId !== false) {
            return (int) $existingId;
        }

        $insert = $this->pdo->prepare('INSERT INTO customers (tenant_id, fullname) VALUES (:tenant_id, :fullname)');
        $insert->execute([':tenant_id' => $tenantId, ':fullname' => $customerName]);
        return (int) $this->pdo->lastInsertId();
    }

    /**
     * @param array<string, mixed> $data
     * @return array{errors:array<int, string>, customer_name:string, contract_type:string, provider_id:int, notes:?string}
     */
    private function validate(array $data): array
    {
        $errors = [];
        $customerName = trim((string) ($data['energy_customer_name'] ?? $data['customer_name'] ?? ''));
        if ($customerName === '') {
            $errors[] = 'Il nome cliente è obbligatorio.';
        }

        $contractType = (string) ($data['energy_contract_type'] ?? $data['contract_type'] ?? '');
        if (!in_array($contractType, ['luce', 'gas', 'luce_gas'], true)) {
            $errors[] = 'Seleziona la tipologia contratto (luce, gas o luce+gas).';
            $contractType = 'luce';
        }

        $providerId = (int) ($data['energy_provider_id'] ?? $data['provider_id'] ?? 0);
        if ($providerId <= 0) {
            $errors[] = 'Seleziona un gestore energia valido.';
        }

        $notes = trim((string) ($data['energy_contract_notes'] ?? $data['notes'] ?? ''));
        if ($notes === '') {
            $notes = null;
        }

        return [
            'errors' => $errors,
            'customer_name' => $customerName,
            'contract_type' => $contractType,
            'provider_id' => $providerId,
            'notes' => $notes,
        ];
    }

    /**
     * @return array{start: DateTimeImmutable, end: DateTimeImmutable, label: string}
     */
    private function resolvePeriodBounds(string $period, ?string $date = null): array
    {
        $period = in_array($period, ['day', 'month', 'year'], true) ? $period : 'month';
        if ($date !== null && $date !== '') {
            try {
                $anchor = new DateTimeImmutable($date);
            } catch (\Throwable) {
                $anchor = new DateTimeImmutable('today');
            }
        } else {
            $anchor = new DateTimeImmutable('today');
        }

        return match ($period) {
            'year' => $this->resolveYearBounds($anchor),
            'day' => $this->resolveDayBounds($anchor),
            default => $this->resolveMonthBounds($anchor),
        };
    }

    /**
     * @return array{start: DateTimeImmutable, end: DateTimeImmutable, label: string}
     */
    private function resolveDayBounds(DateTimeImmutable $anchor): array
    {
        $start = $anchor->setTime(0, 0, 0);
        $end = $start->setTime(23, 59, 59);

        return [
            'start' => $start,
            'end' => $end,
            'label' => 'Giorno ' . $start->format('d/m/Y'),
        ];
    }

    /**
     * @return array{start: DateTimeImmutable, end: DateTimeImmutable, label: string}
     */
    private function resolveMonthBounds(DateTimeImmutable $anchor): array
    {
        $start = $anchor->modify('first day of this month')->setTime(0, 0, 0);
        $end = $anchor->modify('last day of this month')->setTime(23, 59, 59);

        return [
            'start' => $start,
            'end' => $end,
            'label' => 'Mese ' . $start->format('m/Y'),
        ];
    }

    /**
     * @return array{start: DateTimeImmutable, end: DateTimeImmutable, label: string}
     */
    private function resolveYearBounds(DateTimeImmutable $anchor): array
    {
        $start = $anchor->setDate((int) $anchor->format('Y'), 1, 1)->setTime(0, 0, 0);
        $end = $start->setDate((int) $start->format('Y'), 12, 31)->setTime(23, 59, 59);

        return [
            'start' => $start,
            'end' => $end,
            'label' => 'Anno ' . $start->format('Y'),
        ];
    }
}
