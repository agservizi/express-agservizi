<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Services\EnergyContractService;

final class EnergyContractController
{
    public function __construct(private EnergyContractService $service)
    {
    }

    /**
     * @param array<string, mixed> $input
     * @return array{success:bool, message:string, errors?:array<int, string>}
     */
    public function create(array $input, ?int $userId = null): array
    {
        return $this->service->createContract($input, $userId);
    }

    /**
     * @return array{rows: array<int, array<string, mixed>>, total: float, period_label: string, range: array{start:string,end:string}}
     */
    public function listByPeriod(string $period = 'month', ?string $date = null): array
    {
        return $this->service->listByPeriod($period, $date);
    }

    /**
     * @return array{success:bool, message:string, errors?:array<int, string>}
     */
    public function delete(int $contractId): array
    {
        return $this->service->deleteContract($contractId);
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function search(string $term, int $limit = 5): array
    {
        return $this->service->searchContracts($term, $limit);
    }
}
