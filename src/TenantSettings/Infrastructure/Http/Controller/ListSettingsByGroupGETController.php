<?php

declare(strict_types=1);

namespace Src\TenantSettings\Infrastructure\Http\Controller;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use InvalidArgumentException;
use Src\TenantSettings\Application\UseCases\ListSettingsByGroupUseCase;

final class ListSettingsByGroupGETController extends Controller
{
    public function __construct(
        private readonly ListSettingsByGroupUseCase $useCase
    ) {}

    public function __invoke(string $group): JsonResponse
    {
        try {
            $settings = $this->useCase->execute($group);

            return response()->json([
                'status' => 'success',
                'code' => 200,
                'message' => 'Parámetros del grupo consultados con éxito.',
                'data' => array_map(fn ($s) => $s->toArray(), $settings),
            ]);
        } catch (InvalidArgumentException $e) {
            return response()->json([
                'status' => 'error',
                'code' => 422,
                'message' => $e->getMessage(),
            ], 422);
        } catch (\Throwable $e) {
            return response()->json([
                'status' => 'error',
                'code' => 500,
                'message' => 'Error al listar grupo: '.$e->getMessage(),
            ], 500);
        }
    }
}
