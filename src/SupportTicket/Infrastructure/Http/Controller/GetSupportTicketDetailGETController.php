<?php

declare(strict_types=1);

namespace Src\SupportTicket\Infrastructure\Http\Controller;

use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Src\Shared\Helper\ApiResponse;
use Src\SupportTicket\Application\UseCase\GetSupportTicketDetailUseCase;

final class GetSupportTicketDetailGETController
{
    public function __construct(
        private readonly GetSupportTicketDetailUseCase $useCase
    ) {}

    public function __invoke(Request $request, string $id): JsonResponse
    {
        $userId = (string) ($request->query('user_id') 
            ?: $request->input('user_id') 
            ?: auth('central_customer')->id() 
            ?: auth()->id());

        try {
            $ticket = $this->useCase->execute($id, $userId ?: null);

            return ApiResponse::success(
                data: $ticket,
                message: 'Detalle de ticket obtenido correctamente',
                code: 200
            );
        } catch (Exception $e) {
            return ApiResponse::error(
                message: $e->getMessage(),
                code: (int) ($e->getCode() ?: 404)
            );
        }
    }
}
