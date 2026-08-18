<?php

declare(strict_types=1);

namespace Src\TenantSettings\Infrastructure\Http\Controller;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Src\TenantSettings\Application\UseCases\DeleteSettingUseCase;
use Src\TenantSettings\Domain\Exceptions\InvalidSettingKeyException;

final class DeleteSettingDELETEController extends Controller
{
    public function __construct(
        private readonly DeleteSettingUseCase $useCase
    ) {}

    public function __invoke(string $key): JsonResponse
    {
        try {
            $this->useCase->execute($key);

            return response()->json([
                'status' => 'success',
                'code' => 200,
                'message' => 'Parámetro eliminado con éxito.',
                'data' => null,
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
                'message' => 'Error al eliminar parámetro: '.$e->getMessage(),
            ], 500);
        }
    }
}
