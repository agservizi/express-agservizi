<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Services\PdaImportService;

final class PdaImportController
{
    public function __construct(private PdaImportService $pdaImportService)
    {
    }

    /**
     * @param array<string, mixed> $files
     * @param array<string, mixed> $input
     * @param array<string, mixed>|null $currentUser
     * @return array{success:bool,message:string,warnings?:array<int,string>,errors?:array<int,string>,prefill?:array<string,mixed>}
     */
    public function upload(array $files, array $input, ?array $currentUser = null): array
    {
        $file = $files['pda_file'] ?? null;
        return $this->pdaImportService->processUpload(
            is_array($file) ? $file : null,
            $input,
            $currentUser
        );
    }

    /**
     * @param array<string, mixed> $input
     * @param array<string, mixed>|null $currentUser
     * @return array{success:bool,message:string,warnings?:array<int,string>,errors?:array<int,string>,prefill?:array<string,mixed>}
     */
    public function confirm(array $input, ?array $currentUser = null): array
    {
        return $this->pdaImportService->confirmImport($input, $currentUser);
    }

    /**
     * @param array<string, mixed> $input
     * @param array<string, mixed>|null $currentUser
     * @return array{success:bool,message:string,errors?:array<int,string>}
     */
    public function cancel(array $input, ?array $currentUser = null): array
    {
        return $this->pdaImportService->cancelImport($input, $currentUser);
    }

    /**
     * @return array{rows:array<int,array<string,mixed>>,pagination:array<string,int|bool>}
     */
    public function list(int $page, int $perPage): array
    {
        return $this->pdaImportService->listImports($page, $perPage);
    }

    public function detail(int $id): ?array
    {
        return $this->pdaImportService->getImportDetail($id);
    }

    /**
     * @param array<string, mixed>|null $currentUser
     * @return array{success:bool,message:string,warnings?:array<int,string>,errors?:array<int,string>,preview?:array<string,mixed>}
     */
    public function reprocess(int $id, ?array $currentUser = null): array
    {
        return $this->pdaImportService->reprocessImport($id, $currentUser);
    }
}
