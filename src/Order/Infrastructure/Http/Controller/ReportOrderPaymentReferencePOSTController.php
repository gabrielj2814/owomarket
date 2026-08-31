<?php

declare(strict_types=1);

namespace Src\Order\Infrastructure\Http\Controller;

use App\Http\Controllers\Controller;
use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Src\Order\Application\UseCases\ReportOrderPaymentReferenceUseCase;
use Src\Shared\Helper\ApiResponse;

final class ReportOrderPaymentReferencePOSTController extends Controller
{
    public function __construct(
        private readonly ReportOrderPaymentReferenceUseCase $useCase
    ) {}

    public function __invoke(Request $request, string $id): JsonResponse
    {
        $request->validate([
            'reference' => 'required|string|max:255',
            'notes' => 'nullable|string|max:1000',
        ]);

        try {
            $this->useCase->execute(
                $id,
                (string) $request->input('reference'),
                $request->input('notes')
            );

            return ApiResponse::success(
                data: null,
                message: 'Referencia reportada. La plataforma la coteja contra su banco y confirma el cobro.'
            );
        } catch (Exception $e) {
            return ApiResponse::error(message: $e->getMessage(), code: 422);
        }
    }
}
