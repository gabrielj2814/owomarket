<?php

namespace Src\Admin\Infrastructure\Http\Controller;

use App\Http\Controllers\Controller;
use Exception;
use Illuminate\Http\JsonResponse;
use Src\Admin\Application\UseCase\UpdateAdminProfileUseCase;
use Src\Admin\Infrastructure\Http\Request\UpdateAdminProfileRequest;
use Src\Shared\Helper\ApiResponse;

class UpdateAdminProfilePUTController extends Controller
{
    private UpdateAdminProfileUseCase $useCase;

    public function __construct(UpdateAdminProfileUseCase $useCase)
    {
        $this->useCase = $useCase;
    }

    public function index(UpdateAdminProfileRequest $request, string $user_uuid): JsonResponse
    {
        try {
            $updatedAdmin = $this->useCase->execute(
                $user_uuid,
                $request->input('name'),
                $request->input('phone')
            );

            $dataResponse = [
                'id' => $updatedAdmin->getId()->value(),
                'name' => $updatedAdmin->getName()->value(),
                'phone' => $updatedAdmin->getPhone()?->value() ?? '',
            ];

            return ApiResponse::success(data: $dataResponse, message: 'Perfil actualizado exitosamente', code: 200);
        } catch (Exception $e) {
            return ApiResponse::error(message: $e->getMessage(), code: 400);
        }
    }
}
