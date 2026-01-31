<?php
declare(strict_types=1);

namespace App\Services;

use DateInterval;
use DateTimeImmutable;
use PDO;
use App\Services\TenantContext;

final class ReportsService
{
    private const PAYMENT_METHODS = ['Contanti', 'Carta', 'POS'];

    /**
     * @var array<int, array{id:int,name:string}>
     */
    private ?array $operatorCache = null;

    public function __construct(private PDO $pdo)
    {
    }

    /**
     * @param array<string, mixed> $filters
     * @return array<string, mixed>
     */
    public function buildReport(string $granularity, DateTimeImmutable $referenceDate, array $filters = []): array
    {
        $mode = $this->normalizeGranularity($granularity);
        [$start, $end] = $this->resolveWindow($mode, $referenceDate);
        $selectedFilters = $this->sanitizeFilters($filters);
        $totals = $this->fetchTotals($start, $end, $selectedFilters);
        $payments = $this->fetchPaymentBreakdown($start, $end, $selectedFilters);
        $operators = $this->fetchOperatorBreakdown($start, $end, $selectedFilters);
        $trend = $this->fetchTrend($mode, $referenceDate, $selectedFilters);
        $energyTotals = $this->fetchEnergyTotals($start, $end);
        $energyProviders = $this->fetchEnergyProviderBreakdown($start, $end);
        $energyTypes = $this->fetchEnergyTypeBreakdown($start, $end);
        $energyTrend = $this->fetchEnergyTrend($mode, $referenceDate);

        return [
            'granularity' => $mode,
            'period' => [
                'start' => $start->format('Y-m-d'),
                'end' => $end->format('Y-m-d'),
                'label' => $this->formatPeriodLabel($mode, $start, $end),
                'reference' => $referenceDate->format('Y-m-d'),
            ],
            'totals' => $totals,
            'payments' => $payments,
            'operators' => $operators,
            'trend' => $trend,
            'energy' => [
                'totals' => $energyTotals,
                'providers' => $energyProviders,
                'types' => $energyTypes,
                'trend' => $energyTrend,
            ],
            'selected_filters' => $selectedFilters,
            'filter_options' => [
                'payments' => self::PAYMENT_METHODS,
                'operators' => $this->listOperators(),
            ],
        ];
    }

    /**
     * @return array{contracts:int,total_commission:float,average_commission:float}
     */
    private function fetchEnergyTotals(DateTimeImmutable $start, DateTimeImmutable $end): array
    {
        $tenantId = TenantContext::id();
        $stmt = $this->pdo->prepare(
            'SELECT COUNT(*) AS contract_count, COALESCE(SUM(token_value), 0) AS total_commission
             FROM energy_contracts
             WHERE tenant_id = :tenant_id AND created_at >= :start AND created_at < :end'
        );
        $stmt->execute([
            ':tenant_id' => $tenantId,
            ':start' => $start->format('Y-m-d H:i:s'),
            ':end' => $end->format('Y-m-d H:i:s'),
        ]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC) ?: [
            'contract_count' => 0,
            'total_commission' => 0.0,
        ];

        $count = (int) ($row['contract_count'] ?? 0);
        $total = (float) ($row['total_commission'] ?? 0.0);

        return [
            'contracts' => $count,
            'total_commission' => $total,
            'average_commission' => $count > 0 ? $total / $count : 0.0,
        ];
    }

    /**
     * @return array<int, array{name:string,contracts:int,total_commission:float}>
     */
    private function fetchEnergyProviderBreakdown(DateTimeImmutable $start, DateTimeImmutable $end): array
    {
        $tenantId = TenantContext::id();
        $stmt = $this->pdo->prepare(
            'SELECT ep.name AS provider_name,
                    COUNT(*) AS contract_count,
                    COALESCE(SUM(ec.token_value), 0) AS total_commission
             FROM energy_contracts ec
             LEFT JOIN energy_providers ep ON ep.id = ec.provider_id
             WHERE ec.tenant_id = :tenant_id AND ec.created_at >= :start AND ec.created_at < :end
             GROUP BY ec.provider_id, provider_name
             ORDER BY total_commission DESC, contract_count DESC
             LIMIT 8'
        );
        $stmt->execute([
            ':tenant_id' => $tenantId,
            ':start' => $start->format('Y-m-d H:i:s'),
            ':end' => $end->format('Y-m-d H:i:s'),
        ]);
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];

        $result = [];
        foreach ($rows as $row) {
            $name = (string) ($row['provider_name'] ?? 'Gestore');
            if ($name === '') {
                $name = 'Gestore';
            }
            $result[] = [
                'name' => $name,
                'contracts' => (int) ($row['contract_count'] ?? 0),
                'total_commission' => (float) ($row['total_commission'] ?? 0.0),
            ];
        }

        return $result;
    }

    /**
     * @return array<int, array{type:string,contracts:int,total_commission:float}>
     */
    private function fetchEnergyTypeBreakdown(DateTimeImmutable $start, DateTimeImmutable $end): array
    {
        $tenantId = TenantContext::id();
        $stmt = $this->pdo->prepare(
            'SELECT contract_type,
                    COUNT(*) AS contract_count,
                    COALESCE(SUM(token_value), 0) AS total_commission
             FROM energy_contracts
             WHERE tenant_id = :tenant_id AND created_at >= :start AND created_at < :end
             GROUP BY contract_type
             ORDER BY total_commission DESC, contract_count DESC'
        );
        $stmt->execute([
            ':tenant_id' => $tenantId,
            ':start' => $start->format('Y-m-d H:i:s'),
            ':end' => $end->format('Y-m-d H:i:s'),
        ]);
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];

        $result = [];
        foreach ($rows as $row) {
            $type = (string) ($row['contract_type'] ?? '');
            if ($type === '') {
                $type = 'n/d';
            }
            $label = match ($type) {
                'luce' => 'Luce',
                'gas' => 'Gas',
                'luce_gas' => 'Luce + Gas',
                default => ucfirst($type),
            };
            $result[] = [
                'type' => $label,
                'contracts' => (int) ($row['contract_count'] ?? 0),
                'total_commission' => (float) ($row['total_commission'] ?? 0.0),
            ];
        }

        return $result;
    }

    /**
     * @return array{points:array<int, array<string, int|float|string>>, total_count:int, total_commission:float}
     */
    private function fetchEnergyTrend(string $granularity, DateTimeImmutable $reference): array
    {
        $config = $this->resolveTrendConfig($granularity, $reference);
        $tenantId = TenantContext::id();

        $stmt = $this->pdo->prepare(
            'SELECT
                ' . $config['bucket_expression'] . ' AS bucket,
                COUNT(*) AS contract_count,
                COALESCE(SUM(token_value), 0) AS total_commission
            FROM energy_contracts
            WHERE tenant_id = :tenant_id AND created_at >= :start AND created_at < :end
            GROUP BY bucket
            ORDER BY bucket ASC'
        );
        $stmt->execute([
            ':tenant_id' => $tenantId,
            ':start' => $config['start']->format('Y-m-d H:i:s'),
            ':end' => $config['end']->format('Y-m-d H:i:s'),
        ]);
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];

        $interval = new DateInterval($config['interval_spec']);
        $buckets = [];
        $cursor = $config['start'];
        while ($cursor < $config['end']) {
            $key = $cursor->format($config['key_format']);
            $buckets[$key] = [
                'key' => $key,
                'label' => $cursor->format($config['label_format']),
                'contract_count' => 0,
                'total_commission' => 0.0,
            ];
            $cursor = $cursor->add($interval);
        }

        foreach ($rows as $row) {
            $key = (string) ($row['bucket'] ?? '');
            if (!isset($buckets[$key])) {
                continue;
            }
            $buckets[$key]['contract_count'] = (int) ($row['contract_count'] ?? 0);
            $buckets[$key]['total_commission'] = (float) ($row['total_commission'] ?? 0.0);
        }

        $points = array_values($buckets);
        $totalCount = 0;
        $totalCommission = 0.0;
        foreach ($points as $point) {
            $totalCount += (int) ($point['contract_count'] ?? 0);
            $totalCommission += (float) ($point['total_commission'] ?? 0.0);
        }

        return [
            'points' => $points,
            'total_count' => $totalCount,
            'total_commission' => $totalCommission,
        ];
    }

    private function normalizeGranularity(string $granularity): string
    {
        return match (strtolower($granularity)) {
            'day', 'giorno', 'daily' => 'daily',
            'month', 'mese', 'monthly' => 'monthly',
            'year', 'anno', 'yearly' => 'yearly',
            default => 'daily',
        };
    }

    /**
     * @return array{0:DateTimeImmutable,1:DateTimeImmutable}
     */
    private function resolveWindow(string $granularity, DateTimeImmutable $reference): array
    {
        $start = $reference->setTime(0, 0, 0);

        return match ($granularity) {
            'monthly' => [
                $start->modify('first day of this month'),
                $start->modify('first day of next month'),
            ],
            'yearly' => [
                $start->setDate((int) $start->format('Y'), 1, 1),
                $start->setDate((int) $start->format('Y') + 1, 1, 1),
            ],
            default => [
                $start,
                $start->add(new DateInterval('P1D')),
            ],
        };
    }

    private function formatPeriodLabel(string $granularity, DateTimeImmutable $start, DateTimeImmutable $end): string
    {
        return match ($granularity) {
            'monthly' => $start->format('m/Y'),
            'yearly' => $start->format('Y'),
            default => $start->format('d/m/Y'),
        };
    }

    /**
     * @param array<string, mixed> $filters
     * @return array{payment:?string,operator_id:?int}
     */
    private function sanitizeFilters(array $filters): array
    {
        $selected = [
            'payment' => null,
            'operator_id' => null,
        ];

        if (isset($filters['payment'])) {
            $candidate = (string) $filters['payment'];
            if (in_array($candidate, self::PAYMENT_METHODS, true)) {
                $selected['payment'] = $candidate;
            }
        }

        if (isset($filters['operator_id'])) {
            $operatorId = (int) $filters['operator_id'];
            if ($operatorId > 0 && $this->operatorExists($operatorId)) {
                $selected['operator_id'] = $operatorId;
            }
        }

        return $selected;
    }

    /**
     * @param array{payment:?string,operator_id:?int} $filters
     * @return array{0:string,1:array<string, int|string>}
     */
    private function buildWhereClause(DateTimeImmutable $start, DateTimeImmutable $end, array $filters, string $alias = 'sales'): array
    {
        $tenantId = TenantContext::id();
        $conditions = [
            sprintf('%s.tenant_id = :tenant_id', $alias),
            sprintf('%s.status IN ("Completed", "Refunded")', $alias),
            sprintf('%s.created_at >= :start', $alias),
            sprintf('%s.created_at < :end', $alias),
        ];
        $params = [
            ':tenant_id' => $tenantId,
            ':start' => $start->format('Y-m-d H:i:s'),
            ':end' => $end->format('Y-m-d H:i:s'),
        ];

        if (!empty($filters['payment'])) {
            $conditions[] = sprintf('%s.payment_method = :payment_method', $alias);
            $params[':payment_method'] = (string) $filters['payment'];
        }

        if (!empty($filters['operator_id'])) {
            $conditions[] = sprintf('%s.user_id = :operator_id', $alias);
            $params[':operator_id'] = (int) $filters['operator_id'];
        }

        return [implode(' AND ', $conditions), $params];
    }

    /**
     * @return array<int, array{id:int,name:string}>
     */
    public function listOperators(): array
    {
        if ($this->operatorCache !== null) {
            return $this->operatorCache;
        }

        $tenantId = TenantContext::id();
        $stmt = $this->pdo->prepare(
            'SELECT id, COALESCE(NULLIF(fullname, ""), username, CONCAT("Operatore #", id)) AS name
             FROM users
             WHERE tenant_id = :tenant_id
             ORDER BY name ASC'
        );
        $stmt->execute([':tenant_id' => $tenantId]);
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
        $operators = [];
        foreach ($rows as $row) {
            $operators[] = [
                'id' => (int) ($row['id'] ?? 0),
                'name' => (string) ($row['name'] ?? ''),
            ];
        }

        $this->operatorCache = $operators;

        return $operators;
    }

    private function operatorExists(int $operatorId): bool
    {
        foreach ($this->listOperators() as $operator) {
            if ($operator['id'] === $operatorId) {
                return true;
            }
        }

        return false;
    }

    /**
     * @param array{payment:?string,operator_id:?int} $filters
     * @return array<string, int|float>
     */
    private function fetchTotals(DateTimeImmutable $start, DateTimeImmutable $end, array $filters): array
    {
        [$where, $params] = $this->buildWhereClause($start, $end, $filters);
        $stmt = $this->pdo->prepare(
            'SELECT
                COUNT(*) AS sale_count,
                COALESCE(SUM(total), 0) AS gross_revenue,
                COALESCE(SUM(discount), 0) AS discount_total,
                COALESCE(SUM(refunded_amount), 0) AS refund_total,
                COALESCE(SUM(credited_amount), 0) AS credit_total
            FROM sales
            WHERE ' . $where
        );
        $stmt->execute($params);
        $row = $stmt->fetch(PDO::FETCH_ASSOC) ?: [
            'sale_count' => 0,
            'gross_revenue' => 0.0,
            'discount_total' => 0.0,
            'refund_total' => 0.0,
            'credit_total' => 0.0,
        ];

        $count = (int) $row['sale_count'];
        $gross = (float) $row['gross_revenue'];
        $refund = (float) $row['refund_total'];
        $credit = (float) $row['credit_total'];
        $net = $gross - $refund - $credit;

        return [
            'sales_count' => $count,
            'gross_revenue' => $gross,
            'net_revenue' => $net,
            'discount_total' => (float) $row['discount_total'],
            'refund_total' => $refund,
            'credit_total' => $credit,
            'average_ticket' => $count > 0 ? $gross / $count : 0.0,
            'average_ticket_net' => $count > 0 ? $net / $count : 0.0,
        ];
    }

    /**
     * @param array{payment:?string,operator_id:?int} $filters
     * @return array<int, array<string, float|int|string>>
     */
    private function fetchPaymentBreakdown(DateTimeImmutable $start, DateTimeImmutable $end, array $filters): array
    {
        [$where, $params] = $this->buildWhereClause($start, $end, $filters);
        $stmt = $this->pdo->prepare(
            'SELECT
                payment_method,
                COUNT(*) AS sale_count,
                COALESCE(SUM(total), 0) AS gross_revenue,
                COALESCE(SUM(discount), 0) AS discount_total,
                COALESCE(SUM(refunded_amount), 0) AS refund_total,
                COALESCE(SUM(credited_amount), 0) AS credit_total
            FROM sales
            WHERE ' . $where . '
            GROUP BY payment_method
            ORDER BY gross_revenue DESC'
        );
        $stmt->execute($params);
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];

        $result = [];
        foreach ($rows as $row) {
            $gross = (float) ($row['gross_revenue'] ?? 0.0);
            $refund = (float) ($row['refund_total'] ?? 0.0);
            $credit = (float) ($row['credit_total'] ?? 0.0);
            $net = $gross - $refund - $credit;
            $result[] = [
                'method' => (string) ($row['payment_method'] ?? 'Contanti'),
                'sales_count' => (int) ($row['sale_count'] ?? 0),
                'gross_revenue' => $gross,
                'net_revenue' => $net,
                'discount_total' => (float) ($row['discount_total'] ?? 0.0),
                'refund_total' => $refund,
                'credit_total' => $credit,
            ];
        }

        usort($result, static function (array $a, array $b): int {
            return $b['net_revenue'] <=> $a['net_revenue'];
        });

        return $result;
    }

    /**
     * @param array{payment:?string,operator_id:?int} $filters
     * @return array<int, array<string, float|int|string>>
     */
    private function fetchOperatorBreakdown(DateTimeImmutable $start, DateTimeImmutable $end, array $filters): array
    {
        [$where, $params] = $this->buildWhereClause($start, $end, $filters, 's');
        $stmt = $this->pdo->prepare(
            'SELECT
                s.user_id,
                COALESCE(NULLIF(u.fullname, ""), u.username, CONCAT("Operatore #", s.user_id)) AS operator_name,
                COUNT(*) AS sale_count,
                COALESCE(SUM(total), 0) AS gross_revenue,
                COALESCE(SUM(discount), 0) AS discount_total,
                COALESCE(SUM(refunded_amount), 0) AS refund_total,
                COALESCE(SUM(credited_amount), 0) AS credit_total
            FROM sales s
            LEFT JOIN users u ON u.id = s.user_id
            WHERE ' . $where . '
            GROUP BY s.user_id, operator_name
            ORDER BY gross_revenue DESC
            LIMIT 10'
        );
        $stmt->execute($params);
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];

        $result = [];
        foreach ($rows as $row) {
            $gross = (float) ($row['gross_revenue'] ?? 0.0);
            $refund = (float) ($row['refund_total'] ?? 0.0);
            $credit = (float) ($row['credit_total'] ?? 0.0);
            $net = $gross - $refund - $credit;
            $result[] = [
                'user_id' => (int) ($row['user_id'] ?? 0),
                'operator_name' => (string) ($row['operator_name'] ?? ''),
                'sales_count' => (int) ($row['sale_count'] ?? 0),
                'gross_revenue' => $gross,
                'net_revenue' => $net,
                'discount_total' => (float) ($row['discount_total'] ?? 0.0),
                'refund_total' => $refund,
                'credit_total' => $credit,
            ];
        }

        usort($result, static function (array $a, array $b): int {
            return $b['net_revenue'] <=> $a['net_revenue'];
        });

        return $result;
    }

    /**
     * @param array{payment:?string,operator_id:?int} $filters
     * @return array<string, mixed>
     */
    private function fetchTrend(string $granularity, DateTimeImmutable $reference, array $filters): array
    {
        $config = $this->resolveTrendConfig($granularity, $reference);

        [$where, $params] = $this->buildWhereClause($config['start'], $config['end'], $filters);
        $stmt = $this->pdo->prepare(
            'SELECT
                ' . $config['bucket_expression'] . ' AS bucket,
                COUNT(*) AS sale_count,
                COALESCE(SUM(total), 0) AS gross_revenue,
                COALESCE(SUM(refunded_amount), 0) AS refund_total,
                COALESCE(SUM(credited_amount), 0) AS credit_total
            FROM sales
            WHERE ' . $where . '
            GROUP BY bucket
            ORDER BY bucket ASC'
        );
        $stmt->execute($params);
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];

        $interval = new DateInterval($config['interval_spec']);
        $buckets = [];
        $cursor = $config['start'];
        while ($cursor < $config['end']) {
            $key = $cursor->format($config['key_format']);
            $buckets[$key] = [
                'key' => $key,
                'label' => $cursor->format($config['label_format']),
                'sale_count' => 0,
                'gross_revenue' => 0.0,
                'net_revenue' => 0.0,
            ];
            $cursor = $cursor->add($interval);
        }

        foreach ($rows as $row) {
            $key = (string) ($row['bucket'] ?? '');
            if (!isset($buckets[$key])) {
                continue;
            }
            $gross = (float) ($row['gross_revenue'] ?? 0.0);
            $refund = (float) ($row['refund_total'] ?? 0.0);
            $credit = (float) ($row['credit_total'] ?? 0.0);

            $buckets[$key]['sale_count'] = (int) ($row['sale_count'] ?? 0);
            $buckets[$key]['gross_revenue'] = $gross;
            $buckets[$key]['net_revenue'] = $gross - $refund - $credit;
        }

        $points = array_values($buckets);
        $totalCount = 0;
        $totalGross = 0.0;
        $totalNet = 0.0;
        foreach ($points as $point) {
            $totalCount += (int) $point['sale_count'];
            $totalGross += (float) $point['gross_revenue'];
            $totalNet += (float) $point['net_revenue'];
        }

        return [
            'points' => $points,
            'total_count' => $totalCount,
            'total_gross' => $totalGross,
            'total_net' => $totalNet,
        ];
    }

    /**
     * @return array{
     *   start: DateTimeImmutable,
     *   end: DateTimeImmutable,
     *   bucket_expression: string,
     *   interval_spec: string,
     *   key_format: string,
     *   label_format: string
     * }
     */
    private function resolveTrendConfig(string $granularity, DateTimeImmutable $reference): array
    {
        $referenceStart = $reference->setTime(0, 0, 0);

        switch ($granularity) {
            case 'monthly':
                $base = $referenceStart->modify('first day of this month');
                return [
                    'start' => $base->modify('-11 months'),
                    'end' => $base->modify('+1 month'),
                    'bucket_expression' => 'DATE_FORMAT(created_at, "%Y-%m")',
                    'interval_spec' => 'P1M',
                    'key_format' => 'Y-m',
                    'label_format' => 'm/Y',
                ];
            case 'yearly':
                $base = $referenceStart->setDate((int) $referenceStart->format('Y'), 1, 1);
                return [
                    'start' => $base->modify('-4 years'),
                    'end' => $base->modify('+1 year'),
                    'bucket_expression' => 'DATE_FORMAT(created_at, "%Y")',
                    'interval_spec' => 'P1Y',
                    'key_format' => 'Y',
                    'label_format' => 'Y',
                ];
            default:
                return [
                    'start' => $referenceStart->modify('-6 days'),
                    'end' => $referenceStart->add(new DateInterval('P1D')),
                    'bucket_expression' => 'DATE(created_at)',
                    'interval_spec' => 'P1D',
                    'key_format' => 'Y-m-d',
                    'label_format' => 'd/m',
                ];
        }
    }
}
