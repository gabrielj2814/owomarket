<?php

namespace Src\Authentication\Infrastructure\Http\Controller;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Src\Authentication\Application\UseCase\LogoutWebUseCase;
use Src\Authentication\Infrastructure\Eloquent\Repositories\LoginWebRepository;
use Src\Authentication\Infrastructure\Services\ApiGateway;
use Src\Shared\Domain\ValueObjects\Uuid;
use Src\Shared\Helper\ApiResponse;
use Symfony\Component\HttpFoundation\JsonResponse;

class LogoutWebPOSTController extends Controller
{
    /**
     * Constructor de la clase.
     */
    public function __construct(protected ApiGateway $api) {}

    /**
     * Método index.
     */
    public function index(Request $request): JsonResponse
    {
        $uuid = Uuid::make($request->uuid);

        $loginWebRepository = new LoginWebRepository;

        $useCase = new LogoutWebUseCase(
            $loginWebRepository
        );

        $respuesta = $useCase->execute($uuid);
        if (! $respuesta) {
            return ApiResponse::error(message: 'Error al hacer logout', code: 500);
        }

        return ApiResponse::success(data: $respuesta, message: 'Cierre de sesión exitoso', code: 200);
    }
}
