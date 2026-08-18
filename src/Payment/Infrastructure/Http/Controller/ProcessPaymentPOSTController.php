<?php

declare(strict_types=1);

namespace Src\Payment\Infrastructure\Http\Controller;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Src\Payment\Application\UseCases\ProcessPaymentUseCase;
use Src\Payment\Domain\Exceptions\PaymentGatewayNotFoundException;
use Src\Payment\Domain\Exceptions\PaymentProcessingException;
use Src\Payment\Infrastructure\Http\Request\ProcessPaymentFormRequest;
use Src\Shared\Helper\ApiResponse;

final class ProcessPaymentPOSTController extends Controller
{
    public function __construct(
        private readonly ProcessPaymentUseCase $useCase
    ) {}

    public function __invoke(ProcessPaymentFormRequest $request): JsonResponse
    {
        try {
            $dto = $request->toDto();
            $result = $this->useCase->execute($dto);

            return ApiResponse::success(
                data: $result->toArray(),
                message: $result->message ?? 'Pago procesado'
            );
        } catch (PaymentGatewayNotFoundException $e) {
            return ApiResponse::error(
                message: $e->getMessage(),
                code: 404
            );
        } catch (PaymentProcessingException $e) {
            return ApiResponse::error(
                message: $e->getMessage(),
                code: 422
            );
        }
    }
}
