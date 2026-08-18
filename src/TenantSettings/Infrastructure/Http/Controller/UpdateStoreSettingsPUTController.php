<?php

declare(strict_types=1);

namespace Src\TenantSettings\Infrastructure\Http\Controller;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Src\TenantSettings\Application\UseCases\UpdateStoreSettingsUseCase;
use Src\TenantSettings\Infrastructure\Http\Request\UpdateStoreSettingsFormRequest;

final class UpdateStoreSettingsPUTController extends Controller
{
    public function __construct(
        private readonly UpdateStoreSettingsUseCase $useCase
    ) {}

    public function __invoke(UpdateStoreSettingsFormRequest $request): JsonResponse
    {
        try {
            $dto = $request->toDto();
            $updated = $this->useCase->execute($dto);

            return response()->json([
                'status' => 'success',
                'code' => 200,
                'message' => 'Configuración de la tienda actualizada con éxito.',
                'data' => [
                    'grouped' => $updated->toArray(),
                    'flat' => $updated->toKeyValueMap(),
                ],
            ]);
        } catch (\Throwable $e) {
            return response()->json([
                'status' => 'error',
                'code' => 500,
                'message' => 'Error al actualizar configuraciones: '.$e->getMessage(),
            ], 500);
        }
    }
}
