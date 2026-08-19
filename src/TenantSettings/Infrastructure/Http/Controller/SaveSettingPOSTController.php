<?php

declare(strict_types=1);

namespace Src\TenantSettings\Infrastructure\Http\Controller;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Src\TenantSettings\Application\UseCases\SaveSettingUseCase;
use Src\TenantSettings\Domain\Exceptions\InvalidSettingKeyException;
use Src\TenantSettings\Infrastructure\Http\Request\SaveSettingFormRequest;

final class SaveSettingPOSTController extends Controller
{
    public function __construct(
        private readonly SaveSettingUseCase $useCase
    ) {}

    public function __invoke(SaveSettingFormRequest $request): JsonResponse
    {
        try {
            $dto = $request->toDto();
            $setting = $this->useCase->execute($dto);

            return response()->json([
                'status' => 'success',
                'code' => 200,
                'message' => 'Parámetro de configuración guardado con éxito.',
                'data' => $setting->toArray(),
            ]);
        } catch (InvalidSettingKeyException $e) {
            return response()->json([
                'status' => 'error',
                'code' => 422,
                'message' => $e->getMessage(),
            ], 422);
        } catch (\Throwable $e) {
            return response()->json([
                'status' => 'error',
                'code' => 500,
                'message' => 'Error al guardar parámetro: '.$e->getMessage(),
            ], 500);
        }
    }
}
