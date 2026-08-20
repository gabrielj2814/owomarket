<?php

declare(strict_types=1);

namespace Src\Tenant\Infrastructure\Http\Controller;

use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Src\Shared\Helper\ApiResponse;
use Src\Tenant\Application\UseCase\UpdateTenantGovernanceStatusUseCase;

final class UpdateTenantGovernanceStatusPATCHController
{
    public function __construct(
        private readonly UpdateTenantGovernanceStatusUseCase $governanceUseCase
    ) {}

    public function __invoke(Request $request, string $id): JsonResponse
    {
        $request->validate([
            'status' => 'nullable|string|in:active,inactive,suspended',
            'request' => 'nullable|string|in:approved,rejected,in progress',
            'reason' => 'nullable|string|max:500',
            'admin_notes' => 'nullable|string|max:2000',
        ]);

        try {
            $user = auth()->user();
            $adminUserId = (string) ($user?->id ?? 'system_admin');

            $tenant = $this->governanceUseCase->execute($id, $adminUserId, [
                'status' => $request->input('status'),
                'request' => $request->input('request'),
                'reason' => $request->input('reason'),
                'admin_notes' => $request->input('admin_notes'),
            ]);

            return ApiResponse::success(
                data: $tenant,
                message: 'Estado del comercio actualizado exitosamente.'
            );
        } catch (Exception $e) {
            return ApiResponse::error(
                message: $e->getMessage(),
                code: (int) ($e->getCode() ?: 400)
            );
        }
    }
}
