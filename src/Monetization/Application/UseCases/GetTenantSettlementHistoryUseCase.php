<?php

declare(strict_types=1);

namespace Src\Monetization\Application\UseCases;

use App\Models\CommissionSettlement;
use Illuminate\Database\Eloquent\Collection;

final class GetTenantSettlementHistoryUseCase
{
    /**
     * @param string $tenantId
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
