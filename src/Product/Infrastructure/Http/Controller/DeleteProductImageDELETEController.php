<?php

declare(strict_types=1);

namespace Src\Product\Infrastructure\Http\Controller;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Src\Product\Application\UseCase\DeleteProductImageUseCase;
use Src\Shared\Helper\ApiResponse;

final class DeleteProductImageDELETEController extends Controller
{
    public function __construct(
        private readonly DeleteProductImageUseCase $deleteUseCase
    ) {}

    public function __invoke(Request $request): JsonResponse
    {
        $request->validate([
            'image_path' => ['required', 'string', 'max:2048'],
        ]);

        $imagePath = (string) $request->input('image_path');

        // Hallazgo PR1: el mismo `tenant('id')` que usa la subida al escribir la ruta. Es
        // lo que delimita que se puede borrar; sin el, esta ruta aceptaba cualquier fichero
        // del disco publico.
        $this->deleteUseCase->execute(
            $imagePath,
            tenant('id') ? (string) tenant('id') : null
        );

        return ApiResponse::success(
            data: null,
            message: 'Imagen eliminada del almacenamiento exitosamente'
        );
    }
}
