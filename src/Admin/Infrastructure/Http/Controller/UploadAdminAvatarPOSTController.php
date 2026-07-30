<?php

namespace Src\Admin\Infrastructure\Http\Controller;

use App\Http\Controllers\Controller;
use Exception;
use Illuminate\Http\JsonResponse;
use Src\Admin\Application\UseCase\UploadAdminAvatarUseCase;
use Src\Admin\Infrastructure\Http\Request\UploadAdminAvatarRequest;
use Src\Shared\Helper\ApiResponse;

class UploadAdminAvatarPOSTController extends Controller
{
    private UploadAdminAvatarUseCase $useCase;

    public function __construct(UploadAdminAvatarUseCase $useCase)
    {
        $this->useCase = $useCase;
    }

    public function index(UploadAdminAvatarRequest $request, string $user_uuid): JsonResponse
    {
        try {
            $file = $request->file('avatar');
            $updatedAdmin = $this->useCase->execute($user_uuid, $file);

            $dataResponse = [
                'avatar' => $updatedAdmin->getAvatar()?->value(),
            ];

            return ApiResponse::success(data: $dataResponse, message: 'Foto de perfil actualizada exitosamente', code: 200);
        } catch (Exception $e) {
            return ApiResponse::error(message: $e->getMessage(), code: 400);
        }
    }
}
