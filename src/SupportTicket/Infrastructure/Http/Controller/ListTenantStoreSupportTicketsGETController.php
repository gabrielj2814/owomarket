<?php

declare(strict_types=1);

namespace Src\SupportTicket\Infrastructure\Http\Controller;

use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Src\Shared\Helper\ApiResponse;
use Src\SupportTicket\Application\UseCase\ListUserSupportTicketsUseCase;

final class ListTenantStoreSupportTicketsGETController
{
    public function __construct(
        private readonly ListUserSupportTicketsUseCase $useCase
    ) {}

    public function __invoke(Request $request): JsonResponse
    {
        $userId = (string) (auth()->id() ?: $request->input('user_id'));
        $tenantId = function_exists('tenant') && tenant() ? (string) tenant('id') : $request->input('tenant_id');

        if (empty($userId)) {
            return ApiResponse::error('Usuario no autenticado', 401);
        }

        try {
            $data = $this->useCase->execute(
                $userId,
                $request->query('status'),
                $tenantId
            );

            return ApiResponse::success(
                data: $data,
                message: 'Tickets de la tienda obtenidos exitosamente',
                code: 200
            );
        } catch (Exception $e) {
            return ApiResponse::error(
                message: $e->getMessage(),
                code: (int) ($e->getCode() ?: 400)
            );
        }
    }
}
