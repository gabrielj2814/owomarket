<?php

namespace Src\Authentication\Infrastructure\Http\Controller;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Src\Authentication\Application\UseCase\ConsultarAuthUserByUuidUseCase;
use Src\Authentication\Domain\ValueObjects\Uuid;
use Src\Shared\Helper\ApiResponse;

class CurrentUserGETController extends Controller
{
    /**
     * Método index.
     */
    public function __construct(
        protected ConsultarAuthUserByUuidUseCase $consultarAuthUserByUuidUseCase
    ) {}

    public function index(Request $request): JsonResponse
    {
        $uuid = Uuid::make($request->user_uuid);
        $authUser = $this->consultarAuthUserByUuidUseCase->execute($uuid);

        return ApiResponse::success(
            data: [
                'user_id' => $authUser->getUserId()->value(),
                'user_name' => $authUser->getName()->value(),
                'user_email' => $authUser->getEmail()->value(),
                'user_type' => $authUser->getType()->value(),
                'user_avatar' => $authUser->getAvatar()?->value(),
            ],
            message: 'Usuario actual obtenido con éxito',
            code: 200
        );
    }
}
