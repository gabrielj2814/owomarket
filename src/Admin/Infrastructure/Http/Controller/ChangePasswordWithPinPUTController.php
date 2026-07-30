<?php

namespace Src\Admin\Infrastructure\Http\Controller;

use App\Http\Controllers\Controller;
use Exception;
use Illuminate\Http\JsonResponse;
use Src\Admin\Application\UseCase\ChangePasswordWithPinUseCase;
use Src\Admin\Infrastructure\Http\Request\ChangePasswordWithPinRequest;
use Src\Shared\Helper\ApiResponse;

class ChangePasswordWithPinPUTController extends Controller
{
    private ChangePasswordWithPinUseCase $useCase;

    public function __construct(ChangePasswordWithPinUseCase $useCase)
    {
        $this->useCase = $useCase;
    }

    public function index(ChangePasswordWithPinRequest $request, string $user_uuid): JsonResponse
    {
        try {
            $this->useCase->execute(
                $user_uuid,
                $request->input('pin'),
                $request->input('password'),
                $request->input('password_confirmation')
            );

            return ApiResponse::success(
                data: null,
                message: 'Tu contraseña ha sido actualizada exitosamente.',
                code: 200
            );
        } catch (Exception $e) {
            return ApiResponse::error(message: $e->getMessage(), code: 400);
        }
    }
}
