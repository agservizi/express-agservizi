<?php
declare(strict_types=1);

namespace App\Services;

use PDO;
use App\Services\TenantContext;

final class PrivacyPolicyService
{
    public function __construct(private PDO $pdo)
    {
    }

    /**
     * @return array<string, mixed>|null
     */
    public function getActivePolicy(): ?array
    {
        $tenantId = TenantContext::id();
        $stmt = $this->pdo->prepare(
            'SELECT id, version, title, content, is_active, created_at, updated_at
             FROM privacy_policies
             WHERE tenant_id = :tenant_id AND is_active = 1
             ORDER BY updated_at DESC, id DESC
             LIMIT 1'
        );
        $stmt->execute([':tenant_id' => $tenantId]);

        $policy = $stmt->fetch(PDO::FETCH_ASSOC);

        return $policy !== false ? $policy : null;
    }

    /**
     * @return array<string, mixed>|null
     */
    public function findPolicyById(int $policyId): ?array
    {
        $tenantId = TenantContext::id();
        $stmt = $this->pdo->prepare(
            'SELECT id, version, title, content, is_active, created_at, updated_at
             FROM privacy_policies
             WHERE tenant_id = :tenant_id AND id = :id'
        );
        $stmt->execute([':tenant_id' => $tenantId, ':id' => $policyId]);
        $policy = $stmt->fetch(PDO::FETCH_ASSOC);

        return $policy !== false ? $policy : null;
    }

    public function hasAcceptedPolicy(int $portalAccountId, int $policyId): bool
    {
        $tenantId = TenantContext::id();
        $stmt = $this->pdo->prepare(
            'SELECT 1
             FROM privacy_policy_acceptances
             WHERE tenant_id = :tenant_id AND portal_account_id = :account AND policy_id = :policy
             LIMIT 1'
        );
        $stmt->execute([
            ':tenant_id' => $tenantId,
            ':account' => $portalAccountId,
            ':policy' => $policyId,
        ]);

        return $stmt->fetchColumn() !== false;
    }

    public function recordAcceptance(int $portalAccountId, int $policyId, ?string $ipAddress, ?string $userAgent): bool
    {
        $tenantId = TenantContext::id();
        if ($this->hasAcceptedPolicy($portalAccountId, $policyId)) {
            return true;
        }

        $stmt = $this->pdo->prepare(
            'INSERT INTO privacy_policy_acceptances (tenant_id, portal_account_id, policy_id, ip_address, user_agent)
             VALUES (:tenant_id, :account, :policy, :ip, :ua)'
        );

        return $stmt->execute([
            ':tenant_id' => $tenantId,
            ':account' => $portalAccountId,
            ':policy' => $policyId,
            ':ip' => $this->truncateOrNull($ipAddress, 45),
            ':ua' => $this->truncateOrNull($userAgent, 255),
        ]);
    }

    private function truncateOrNull(?string $value, int $length): ?string
    {
        if ($value === null) {
            return null;
        }

        $trimmed = trim($value);
        if ($trimmed === '') {
            return null;
        }

        if (function_exists('mb_substr')) {
            return mb_substr($trimmed, 0, $length);
        }

        return substr($trimmed, 0, $length);
    }
}
