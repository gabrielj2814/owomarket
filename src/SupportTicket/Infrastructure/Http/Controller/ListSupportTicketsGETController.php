<?php

declare(strict_types=1);

namespace Src\SupportTicket\Infrastructure\Http\Controller;

use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Src\Shared\Helper\ApiResponse;
use Src\SupportTicket\Application\UseCase\ListUserSupportTicketsUseCase;

final class ListSupportTicketsGETController
{
    public function __construct(
        private readonly ListUserSupportTicketsUseCase $useCase
    ) {}

    public function __invoke(Request $request): JsonResponse
    {
        $userId = (string) ($request->query('user_id') 
            ?: $request->input('user_id') 
            ?: auth('central_customer')->id() 
            ?: auth()->id());

        if (empty($userId)) {
            return ApiResponse::error('El parámetro user_id es obligatorio', 400);
        }

        try {
            $data = $this->useCase->execute(
                $userId,
                $request->query('status'),
                $request->query('tenant_id')
            );

            return ApiResponse::success(
                data: $data,
                message: 'Tickets obtenidos correctamente',
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
