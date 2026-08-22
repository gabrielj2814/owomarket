<?php

declare(strict_types=1);

namespace Src\Admin\Application\UseCase;

use Illuminate\Pagination\LengthAwarePaginator;
use Src\Admin\Infrastructure\Eloquent\Models\CentralAuditLog;

final class ListCentralAuditLogsUseCase
{
    /**
     * @param array{
     *     action?: string|null,
     *     entity_type?: string|null,
     *     search?: string|null,
     *     per_page?: int,
     *     page?: int
     * } $filters
     * @return array{
     *     logs: LengthAwarePaginator,
     *     metrics: array{
     *         total_logs: int,
     *         security_actions: int,
     *         financial_actions: int
     *     },
     *     actions_list: array<string>
     * }
     */
    public function execute(array $filters): array
    {
        $query = CentralAuditLog::query();

        if (! empty($filters['action'])) {
            $query->where('action', $filters['action']);
        }

        if (! empty($filters['entity_type'])) {
            $query->where('entity_type', $filters['entity_type']);
        }

        if (! empty($filters['search'])) {
            $search = trim($filters['search']);
            $query->where(function ($q) use ($search) {
                $q->where('user_name', 'like', "%{$search}%")
                    ->orWhere('user_email', 'like', "%{$search}%")
                    ->orWhere('description', 'like', "%{$search}%")
                    ->orWhere('entity_id', 'like', "%{$search}%");
            });
        }

        $perPage = (int) ($filters['per_page'] ?? 20);
        $logs = $query->orderBy('created_at', 'desc')->paginate($perPage);

        $totalLogs = CentralAuditLog::count();
        $securityActions = CentralAuditLog::where('action', 'like', '%role%')->orWhere('action', 'like', '%governance%')->count();
        $financialActions = CentralAuditLog::where('action', 'like', '%payout%')->orWhere('action', 'like', '%dispute%')->count();

        $actionsList = CentralAuditLog::select('action')->distinct()->pluck('action')->toArray();

        return [
            'logs' => $logs,
            'metrics' => [
                'total_logs' => $totalLogs,
                'security_actions' => $securityActions,
                'financial_actions' => $financialActions,
            ],
            'actions_list' => $actionsList,
        ];
    }
}
