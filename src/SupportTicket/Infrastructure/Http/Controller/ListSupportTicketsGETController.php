<?php

declare(strict_types=1);

namespace Src\SupportTicket\Infrastructure\Http\Controller;

use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Src\Shared\Helper\ApiResponse;
use Src\SupportTicket\Application\UseCase\ListUserSupportTicketsUseCase;
use Src\SupportTicket\Infrastructure\Http\Support\ResolvesSupportRequester;

final class ListSupportTicketsGETController
{
    use ResolvesSupportRequester;

    public function __construct(
        private readonly ListUserSupportTicketsUseCase $useCase
    ) {}

    public function __invoke(Request $request): JsonResponse
    {
        // La identidad SIEMPRE sale de la sesión, nunca del request (hallazgo A6).
        $requester = $this->resolveSupportRequester($request);

        if ($requester === null) {
            return ApiResponse::error('Debes iniciar sesión para acceder a soporte.', 401);
        }

        try {
            $data = $this->useCase->execute(
                $requester['id'],
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
