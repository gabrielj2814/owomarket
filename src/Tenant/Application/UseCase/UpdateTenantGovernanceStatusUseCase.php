<?php

declare(strict_types=1);

namespace Src\Tenant\Application\UseCase;

use Exception;
use Src\Tenant\Infrastructure\Eloquent\Models\Tenant;

final class UpdateTenantGovernanceStatusUseCase
{
    /**
     * @param array{
     *     status?: 'active'|'inactive'|'suspended',
     *     request?: 'approved'|'rejected'|'in progress',
     *     reason?: string|null,
     *     admin_notes?: string|null
     * } $data
     */
    public function execute(string $tenantId, string $adminUserId, array $data): Tenant
    {
        $tenant = Tenant::find($tenantId);

        if (! $tenant) {
            throw new Exception("Tienda inquilina '{$tenantId}' no encontrada.", 404);
        }

        if (isset($data['admin_notes'])) {
            $tenant->admin_notes = $data['admin_notes'];
        }

        if (! empty($data['status'])) {
            $tenant->status = $data['status'];
        }

        if (! empty($data['request'])) {
            $tenant->request = $data['request'];
            if ($data['request'] === 'approved' && empty($data['status'])) {
                $tenant->status = 'active';
            }
        }

        $history = $tenant->governance_history ?? [];
        if (! empty($data['reason'])) {
            $history[] = [
                'action_by' => $adminUserId,
                'status' => $tenant->status,
                'request' => $tenant->request,
                'reason' => $data['reason'],
                'timestamp' => now()->toIso8601String(),
            ];
            $tenant->governance_history = $history;
        }

        $tenant->save();

        return $tenant;
    }
}
