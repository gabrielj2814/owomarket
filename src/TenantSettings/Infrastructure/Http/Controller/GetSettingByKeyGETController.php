<?php

declare(strict_types=1);

namespace Src\TenantSettings\Infrastructure\Http\Controller;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Src\TenantSettings\Application\UseCases\GetSettingByKeyUseCase;
use Src\TenantSettings\Domain\Exceptions\InvalidSettingKeyException;
use Src\TenantSettings\Domain\Exceptions\SettingNotFoundException;

final class GetSettingByKeyGETController extends Controller
{
    public function __construct(
        private readonly GetSettingByKeyUseCase $useCase
    ) {}

    public function __invoke(string $key): JsonResponse
    {
        try {
            $setting = $this->useCase->execute($key);

            return response()->json([
                'status' => 'success',
                'code' => 200,
                'message' => 'Parámetro de configuración consultado con éxito.',
                'data' => $setting->toArray(),
            ]);
        } catch (SettingNotFoundException $e) {
            return response()->json([
                'status' => 'error',
                'code' => 404,
                'message' => $e->getMessage(),
            ], 404);
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
                'message' => 'Error al consultar parámetro: '.$e->getMessage(),
            ], 500);
        }
    }
}
