<?php

declare(strict_types=1);

namespace Src\Product\Infrastructure\Http\Controller;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Src\Product\Application\UseCase\UploadProductImageUseCase;
use Src\Product\Infrastructure\Http\Request\UploadProductImageFormRequest;
use Src\Shared\Helper\ApiResponse;

final class UploadProductImagePOSTController extends Controller
{
    public function __construct(
        private readonly UploadProductImageUseCase $uploadUseCase
    ) {}

    public function __invoke(UploadProductImageFormRequest $request): JsonResponse
    {
        $file = $request->file('file');
        $tenantId = tenant('id') ? (string) tenant('id') : null;

        $result = $this->uploadUseCase->execute($file, $tenantId);

        return ApiResponse::success(
            data: [
                'url' => $result['url'],
                'image_path' => $result['url'],
                'path' => $result['path'],
                'filename' => $result['filename'],
                'alt_text' => $request->input('alt_text', ''),
            ],
            message: 'Imagen subida exitosamente',
            code: 201
        );
    }
}
