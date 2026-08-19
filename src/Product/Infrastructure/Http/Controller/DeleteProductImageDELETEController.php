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
        $imagePath = (string) $request->input('image_path', $request->query('image_path', ''));

        if (! empty($imagePath)) {
            $this->deleteUseCase->execute($imagePath);
        }

        return ApiResponse::success(
            data: null,
            message: 'Imagen eliminada del almacenamiento exitosamente'
        );
    }
}
