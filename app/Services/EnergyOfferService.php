<?php
declare(strict_types=1);

namespace App\Services;

use DateTimeImmutable;
use PDO;
use App\Services\TenantContext;

final class EnergyOfferService
{
    public function __construct(private PDO $pdo)
    {
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function listOffersForSimulator(): array
    {
        $tenantId = TenantContext::id();
        $today = (new DateTimeImmutable('today'))->format('Y-m-d');
        $stmt = $this->pdo->prepare(
            'SELECT id, provider_id, provider_name, offer_code, offer_name, supply_type,
                    customer_type, offer_type, price_type,
                    p_fix_f, p_fix_v, p_vol_f1, p_vol_f2, p_vol_f3, p_vol_bf1, p_vol_bf23, p_vol_mono, p_vol, alpha,
                    offer_url, valid_from, valid_to
             FROM energy_offers
             WHERE tenant_id = :tenant_id
               AND (valid_from IS NULL OR valid_from <= :today_from)
               AND (valid_to IS NULL OR valid_to >= :today_to)
             ORDER BY provider_name ASC, offer_name ASC'
        );
        $stmt->execute([
            'tenant_id' => $tenantId,
            'today_from' => $today,
            'today_to' => $today,
        ]);
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];

        $allowedProviders = [
            'enel',
            'a2a',
            'windtre',
            'fastweb',
            'edison',
            'iren',
        ];
        $normalizedAllowed = array_map(static fn (string $item): string => $item, $allowedProviders);

        $normalize = static function (?string $value): string {
            $value = strtolower(trim((string) $value));
            $value = preg_replace('/[^a-z0-9]+/u', ' ', $value) ?? $value;
            return trim(preg_replace('/\s+/', ' ', $value) ?? $value);
        };

        $filtered = array_filter($rows, static function (array $row) use ($normalizedAllowed, $normalize): bool {
            $providerName = $normalize((string) ($row['provider_name'] ?? ''));
            if ($providerName === '') {
                return false;
            }
            foreach ($normalizedAllowed as $allowed) {
                if (str_contains($providerName, $allowed)) {
                    return true;
                }
            }
            return false;
        });

        return array_values($filtered);
    }
}
