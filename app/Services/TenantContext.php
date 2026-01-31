<?php
declare(strict_types=1);

namespace App\Services;

final class TenantContext
{
    private static int $tenantId = 1;

    public static function setTenantId(?int $tenantId): void
    {
        $value = $tenantId !== null && $tenantId > 0 ? $tenantId : 1;
        self::$tenantId = $value;
    }

    public static function id(): int
    {
        return self::$tenantId > 0 ? self::$tenantId : 1;
    }
}
