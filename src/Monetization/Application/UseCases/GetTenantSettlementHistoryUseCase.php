<?php

declare(strict_types=1);

namespace Src\Monetization\Application\UseCases;

use Illuminate\Database\Eloquent\Collection;
use Src\Monetization\Infrastructure\Eloquent\Models\CommissionSettlement;

final class GetTenantSettlementHistoryUseCase
{
    /**
     * @return Collection<int, CommissionSettlement>
     */
    public function execute(string $tenantId): Collection
    {
        return CommissionSettlement::with('commissions')
            ->where('tenant_id', $tenantId)
            ->orderBy('created_at', 'desc')
            ->get();
    }
}
