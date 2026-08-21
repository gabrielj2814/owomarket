<?php

declare(strict_types=1);

namespace Src\SupportTicket\Infrastructure\Http\Controller;

use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Src\Shared\Helper\ApiResponse;
use Src\SupportTicket\Application\UseCase\GetSupportTicketDetailUseCase;
use Src\SupportTicket\Infrastructure\Http\Support\ResolvesSupportRequester;

final class GetSupportTicketDetailGETController
{
    use ResolvesSupportRequester;

    public function __construct(
        private readonly GetSupportTicketDetailUseCase $useCase
    ) {}

    public function __invoke(Request $request, string $id): JsonResponse
    {
        // La identidad SIEMPRE sale de la sesión, nunca del request (hallazgo A6).
        $requester = $this->resolveSupportRequester($request);

        if ($requester === null) {
            return ApiResponse::error('Debes iniciar sesión para acceder a soporte.', 401);
        }

        try {
            $ticket = $this->useCase->execute($id, $requester['id']);

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
