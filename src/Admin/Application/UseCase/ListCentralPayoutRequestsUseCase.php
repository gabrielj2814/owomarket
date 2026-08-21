<?php

declare(strict_types=1);

namespace Src\Admin\Application\UseCase;

use Src\Monetization\Infrastructure\Eloquent\Models\CommissionSettlement;
use Src\ExchangeRate\Domain\Repositories\ExchangeRateRepositoryInterface;

final class ListCentralPayoutRequestsUseCase
{
    public function __construct(
        private readonly ?ExchangeRateRepositoryInterface $exchangeRateRepository = null
    ) {}

    /**
     * @param array{
     *     status?: string|null,
     *     payment_method?: string|null,
     *     search?: string|null,
     *     page?: int,
     *     per_page?: int
     * } $filters
     * @return array{
     *     payouts: array<int, mixed>,
     *     pagination: array{current_page: int, last_page: int, total: int, per_page: int},
     *     metrics: array{
     *         total_pending_usd: float,
     *         total_pending_ves: float,
     *         pending_count: int,
     *         total_paid_usd: float,
     *         paid_count: int,
     *         active_rate: float
     *     }
     * }
     */
    public function execute(array $filters = []): array
    {
        $query = CommissionSettlement::with('tenant')
            ->where('type', 'payout')
            ->orderBy('created_at', 'desc');

        if (! empty($filters['status']) && $filters['status'] !== 'all') {
            $query->where('status', $filters['status']);
        }

        if (! empty($filters['payment_method']) && $filters['payment_method'] !== 'all') {
            $query->where('payment_method', $filters['payment_method']);
        }

        if (! empty($filters['search'])) {
            $term = $filters['search'];
            $query->where(function ($q) use ($term) {
                $q->where('settlement_number', 'like', "%{$term}%")
                    ->orWhere('payment_reference', 'like', "%{$term}%")
                    ->orWhereHas('tenant', function ($t) use ($term) {
                        $t->where('name', 'like', "%{$term}%")
                            ->orWhere('slug', 'like', "%{$term}%");
                    });
            });
        }

        $perPage = (int) ($filters['per_page'] ?? 15);
        $paginated = $query->paginate($perPage);

        // Obtener tasa de cambio activa
        $activeRate = 1.0;
        if ($this->exchangeRateRepository) {
            $rateModel = $this->exchangeRateRepository->getActiveRate();
            if ($rateModel) {
                $activeRate = (float) $rateModel->getRate();
            }
        }

        // Métricas
        $pendingQuery = CommissionSettlement::where('type', 'payout')->where('status', 'pending');
        $totalPendingUsd = (float) $pendingQuery->sum('net_amount');
        $pendingCount = $pendingQuery->count();

        $paidQuery = CommissionSettlement::where('type', 'payout')->where('status', 'settled');
        $totalPaidUsd = (float) $paidQuery->sum('net_amount');
        $paidCount = $paidQuery->count();

        return [
            'payouts' => $paginated->items(),
            'pagination' => [
                'current_page' => $paginated->currentPage(),
                'last_page' => $paginated->lastPage(),
                'total' => $paginated->total(),
                'per_page' => $paginated->perPage(),
            ],
            'metrics' => [
                'total_pending_usd' => round($totalPendingUsd, 2),
                'total_pending_ves' => round($totalPendingUsd * $activeRate, 2),
                'pending_count' => $pendingCount,
                'total_paid_usd' => round($totalPaidUsd, 2),
                'paid_count' => $paidCount,
                'active_rate' => $activeRate,
            ],
        ];
    }
}
