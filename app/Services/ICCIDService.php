<?php
declare(strict_types=1);

namespace App\Services;

use App\Helpers\Validator;
use PDO;
use PDOException;
use App\Services\TenantContext;

final class ICCIDService
{
    public function __construct(private PDO $pdo)
    {
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function listStock(?string $status = null): array
    {
        $tenantId = TenantContext::id();
        if ($status !== null) {
            $stmt = $this->pdo->prepare(
                'SELECT iccid_stock.*, providers.name AS provider_name FROM iccid_stock
                 JOIN providers ON providers.id = iccid_stock.provider_id
                 WHERE iccid_stock.tenant_id = :tenant_id AND status = :status
                 ORDER BY iccid_stock.created_at DESC'
            );
            $stmt->execute([':status' => $status, ':tenant_id' => $tenantId]);
        } else {
            $stmt = $this->pdo->query(
                'SELECT iccid_stock.*, providers.name AS provider_name FROM iccid_stock
                 JOIN providers ON providers.id = iccid_stock.provider_id
                 WHERE iccid_stock.tenant_id = :tenant_id
                 ORDER BY iccid_stock.created_at DESC'
            );
            $stmt->execute([':tenant_id' => $tenantId]);
        }

        return $stmt->fetchAll();
    }

    /**
     * @return array{
     *   rows: array<int, array<string, mixed>>,
     *   pagination: array{page:int, per_page:int, total:int, pages:int}
     * }
     */
    public function paginateStock(int $page, int $perPage, ?string $status = null, ?string $search = null): array
    {
        $page = max($page, 1);
        $perPage = max($perPage, 1);

        $conditions = [];
        $params = [];
        $tenantId = TenantContext::id();
        $conditions[] = 'iccid_stock.tenant_id = :tenant_id';
        $params[':tenant_id'] = $tenantId;

        if ($status !== null) {
            $conditions[] = 'status = :status';
            $params[':status'] = $status;
        }

        $searchTerm = null;
        if ($search !== null) {
            $searchTerm = trim($search);
            if ($searchTerm === '') {
                $searchTerm = null;
            }
        }

        if ($searchTerm !== null) {
            $conditions[] = '(iccid_stock.iccid LIKE :search_iccid OR providers.name LIKE :search_provider)';
            $likeValue = '%' . $searchTerm . '%';
            $params[':search_iccid'] = $likeValue;
            $params[':search_provider'] = $likeValue;
        }

        $where = $conditions === [] ? '' : ('WHERE ' . implode(' AND ', $conditions));

        $countSql = 'SELECT COUNT(*) FROM iccid_stock JOIN providers ON providers.id = iccid_stock.provider_id ' . $where;
        $stmtCount = $this->pdo->prepare($countSql);
        foreach ($params as $key => $value) {
            $stmtCount->bindValue($key, $value);
        }
        $stmtCount->execute();
        $total = (int) $stmtCount->fetchColumn();

        $pages = (int) max((int) ceil($total / $perPage), 1);
        $currentPage = max(1, min($page, $pages));
        $offset = ($currentPage - 1) * $perPage;

        $dataSql = 'SELECT iccid_stock.*, providers.name AS provider_name
            FROM iccid_stock
            JOIN providers ON providers.id = iccid_stock.provider_id
            ' . $where . '
            ORDER BY iccid_stock.created_at DESC
            LIMIT :limit OFFSET :offset';

        $stmtData = $this->pdo->prepare($dataSql);
        foreach ($params as $key => $value) {
            $stmtData->bindValue($key, $value);
        }
        $stmtData->bindValue(':limit', $perPage, PDO::PARAM_INT);
        $stmtData->bindValue(':offset', $offset, PDO::PARAM_INT);
        $stmtData->execute();
        $rows = $stmtData->fetchAll();

        return [
            'rows' => $rows,
            'pagination' => [
                'page' => $currentPage,
                'per_page' => $perPage,
                'total' => $total,
                'pages' => $pages,
            ],
        ];
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function listProviders(): array
    {
        $tenantId = TenantContext::id();
        $stmt = $this->pdo->prepare('SELECT id, name, reorder_threshold FROM providers WHERE tenant_id = :tenant_id ORDER BY name');
        $stmt->execute([':tenant_id' => $tenantId]);
        return $stmt->fetchAll();
    }

    /**
     * @return array{inserted:int, errors:array<int, string>}
     */
    public function importFromCsv(string $tmpFile, int $providerId): array
    {
        $errors = [];
        $inserted = 0;

        $fh = fopen($tmpFile, 'r');
        if ($fh === false) {
            throw new \RuntimeException('Impossibile aprire il file CSV.');
        }

        $this->pdo->beginTransaction();
        try {
            $tenantId = TenantContext::id();
            $stmtInsert = $this->pdo->prepare(
                "INSERT INTO iccid_stock (tenant_id, iccid, provider_id, status, notes)
                 VALUES (:tenant_id, :iccid, :provider, 'InStock', :notes)"
            );

            while (($row = fgetcsv($fh, 1000, ',')) !== false) {
                $iccid = trim($row[0] ?? '');
                $notes = $row[1] ?? null;

                if ($iccid === '' || !Validator::isValidICCID($iccid)) {
                    $errors[] = "ICCID non valido: $iccid";
                    continue;
                }

                try {
                    $stmtInsert->execute([
                        ':tenant_id' => $tenantId,
                        ':iccid' => $iccid,
                        ':provider' => $providerId,
                        ':notes' => $notes,
                    ]);
                    $inserted++;
                } catch (PDOException $exception) {
                    $errors[] = "Errore inserimento $iccid: " . $exception->getMessage();
                }
            }

            $this->pdo->commit();
        } catch (
            \Throwable $exception
        ) {
            $this->pdo->rollBack();
            fclose($fh);
            throw $exception;
        }

        fclose($fh);

        return [
            'inserted' => $inserted,
            'errors' => $errors,
        ];
    }

    /**
     * @param array<int, string> $iccids
     * @return array{success:bool, message:string, error?:string, errors?:array<int, string>, inserted?:int}
     */
    public function addBulkSims(array $iccids, int $providerId, ?string $notes = null): array
    {
        $cleaned = [];
        foreach ($iccids as $value) {
            $trimmed = trim((string) $value);
            if ($trimmed !== '') {
                $cleaned[] = $trimmed;
            }
        }

        if ($cleaned === []) {
            return [
                'success' => false,
                'message' => 'Nessun ICCID valido da importare.',
                'error' => 'Lista ICCID vuota.',
                'errors' => ['Inserisci almeno un ICCID valido.'],
                'inserted' => 0,
            ];
        }

        $errors = [];
        $inserted = 0;

        $this->pdo->beginTransaction();
        try {
            $tenantId = TenantContext::id();
            $stmt = $this->pdo->prepare(
                "INSERT INTO iccid_stock (tenant_id, iccid, provider_id, status, notes)
                 VALUES (:tenant_id, :iccid, :provider, 'InStock', :notes)"
            );

            foreach ($cleaned as $iccid) {
                if (!Validator::isValidICCID($iccid)) {
                    $errors[] = "ICCID non valido: {$iccid}";
                    continue;
                }

                try {
                    $stmt->execute([
                        ':tenant_id' => $tenantId,
                        ':iccid' => $iccid,
                        ':provider' => $providerId,
                        ':notes' => $notes,
                    ]);
                    $inserted++;
                } catch (PDOException $exception) {
                    $errors[] = "Errore inserimento {$iccid}: " . $exception->getMessage();
                }
            }

            $this->pdo->commit();
        } catch (\Throwable $exception) {
            $this->pdo->rollBack();

            return [
                'success' => false,
                'message' => 'Errore durante il caricamento delle SIM.',
                'error' => $exception->getMessage(),
                'errors' => $errors,
                'inserted' => $inserted,
            ];
        }

        if (count($errors) > 50) {
            $errors = array_slice($errors, 0, 50);
            $errors[] = '... altri errori non mostrati.';
        }

        $discarded = count($errors);
        if ($inserted === 0) {
            return [
                'success' => false,
                'message' => 'Nessuna SIM caricata.',
                'error' => 'Tutti gli ICCID sono risultati non validi o duplicati.',
                'errors' => $errors,
                'inserted' => 0,
            ];
        }

        $message = "Caricate {$inserted} SIM";
        if ($discarded > 0) {
            $message .= ", scartate {$discarded}.";
        } else {
            $message .= '.';
        }

        return [
            'success' => $inserted > 0,
            'message' => $message,
            'error' => $discarded > 0 ? 'Alcuni ICCID non validi o duplicati.' : null,
            'errors' => $errors,
            'inserted' => $inserted,
        ];
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function listAvailable(): array
    {
        $tenantId = TenantContext::id();
        $stmt = $this->pdo->prepare(
            "SELECT iccid_stock.*, providers.name AS provider_name
             FROM iccid_stock
             JOIN providers ON providers.id = iccid_stock.provider_id
             WHERE iccid_stock.status = 'InStock' AND iccid_stock.tenant_id = :tenant_id
             ORDER BY iccid_stock.created_at ASC"
        );
        $stmt->execute([':tenant_id' => $tenantId]);

        return $stmt->fetchAll();
    }

    /**
     * @return array{success:bool, message:string, error?:string}
     */
    public function addSim(string $iccid, int $providerId, ?string $notes = null): array
    {
        $iccid = trim($iccid);
        if ($iccid === '' || !Validator::isValidICCID($iccid)) {
            return [
                'success' => false,
                'message' => 'ICCID non valido. Inserire 19-20 cifre.',
                'error' => 'ICCID non valido',
            ];
        }

        try {
            $tenantId = TenantContext::id();
            $stmt = $this->pdo->prepare(
                "INSERT INTO iccid_stock (tenant_id, iccid, provider_id, status, notes)
                 VALUES (:tenant_id, :iccid, :provider, 'InStock', :notes)"
            );
            $stmt->execute([
                ':tenant_id' => $tenantId,
                ':iccid' => $iccid,
                ':provider' => $providerId,
                ':notes' => $notes,
            ]);
        } catch (PDOException $exception) {
            return [
                'success' => false,
                'message' => 'Errore durante il salvataggio dell\'ICCID.',
                'error' => $exception->getMessage(),
            ];
        }

        return [
            'success' => true,
            'message' => 'SIM aggiunta correttamente al magazzino.',
        ];
    }
}
