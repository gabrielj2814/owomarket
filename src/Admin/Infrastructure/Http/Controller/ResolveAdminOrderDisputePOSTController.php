<?php

declare(strict_types=1);

namespace Src\Admin\Infrastructure\Http\Controller;

use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Src\Admin\Application\UseCase\ResolveCentralOrderDisputeUseCase;
use Src\Shared\Helper\ApiResponse;

final class ResolveAdminOrderDisputePOSTController
{
    public function __construct(
        private readonly ResolveCentralOrderDisputeUseCase $useCase
    ) {}

    public function __invoke(Request $request, string $id): JsonResponse
    {
        $request->validate([
            'resolution_type' => 'required|string|in:refund,cancel',
            'reason' => 'required|string|max:500',
            'notes' => 'nullable|string|max:1000',
        ]);

        try {
            $user = auth()->user();
            $adminUserId = (string) ($user?->id ?? 'system_admin');

            $order = $this->useCase->execute($id, $adminUserId, [
                'resolution_type' => $request->input('resolution_type'),
                'reason' => $request->input('reason'),
                'notes' => $request->input('notes'),
            ]);

            $actionText = $request->input('resolution_type') === 'refund' ? 'reembolsada' : 'cancelada';

            return ApiResponse::success(
                data: $order,
                message: "Orden marcada como {$actionText} y disputa registrada exitosamente."
            );
        } catch (Exception $e) {
            return ApiResponse::error(
                message: $e->getMessage(),
                code: (int) ($e->getCode() ?: 400)
            );
        }
    }
}
