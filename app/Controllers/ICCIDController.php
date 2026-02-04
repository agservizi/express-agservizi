<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Services\ICCIDService;

final class ICCIDController
{
    public function __construct(private ICCIDService $iccidService)
    {
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function list(?string $status = null): array
    {
        return $this->iccidService->listStock($status);
    }

    /**
     * @return array{
     *   rows: array<int, array<string, mixed>>,
     *   pagination: array{page:int, per_page:int, total:int, pages:int}
     * }
     */
    public function listPaginated(int $page, int $perPage, ?string $status = null, ?string $search = null, ?int $providerId = null): array
    {
        return $this->iccidService->paginateStock($page, $perPage, $status, $search, $providerId);
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function providers(): array
    {
        return $this->iccidService->listProviders();
    }

    /**
     * @return array<int, string>
     */
    public function statuses(): array
    {
        return $this->iccidService->listStatuses();
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function available(): array
    {
        return $this->iccidService->listAvailable();
    }

    /**
     * @param array<string, mixed> $input
     * @return array{success:bool, message:string, error?:string, errors?:array<int, string>, inserted?:int}
     */
    public function create(array $input): array
    {
        $providerId = (int) ($input['provider_id'] ?? 0);
        if ($providerId <= 0) {
            return [
                'success' => false,
                'message' => 'Seleziona un operatore valido.',
                'error' => 'Provider non valido',
            ];
        }

        $action = (string) ($input['action'] ?? '');
        $bulkRaw = (string) ($input['bulk_iccids'] ?? '');
        if ($action === 'bulk_add' || $bulkRaw !== '') {
            $notes = $input['bulk_notes'] !== null ? (string) $input['bulk_notes'] : null;
            $tokens = preg_split('/[\s,;]+/', $bulkRaw, -1, PREG_SPLIT_NO_EMPTY) ?: [];

            return $this->iccidService->addBulkSims($tokens, $providerId, $notes);
        }

        $iccid = (string) ($input['iccid'] ?? '');
        $notes = $input['notes'] !== null ? (string) $input['notes'] : null;

        return $this->iccidService->addSim($iccid, $providerId, $notes);
    }
}
