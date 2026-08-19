<?php

declare(strict_types=1);

namespace Src\TenantSettings\Infrastructure\Http\Controller;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Src\TenantSettings\Application\UseCases\GetStoreSettingsUseCase;

final class GetStoreSettingsGETController extends Controller
{
    public function __construct(
        private readonly GetStoreSettingsUseCase $useCase
    ) {}

    public function __invoke(): JsonResponse
    {
        try {
            $settings = $this->useCase->execute();

            return response()->json([
                'status' => 'success',
                'code' => 200,
                'message' => 'Configuración de la tienda consultada con éxito.',
                'data' => [
                    'grouped' => $settings->toArray(),
                    'flat' => $settings->toKeyValueMap(),
                ],
            ]);
        } catch (\Throwable $e) {
            return response()->json([
                'status' => 'error',
                'code' => 500,
                'message' => 'Error al consultar configuraciones: '.$e->getMessage(),
            ], 500);
        }
    }
}
