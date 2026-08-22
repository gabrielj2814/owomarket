<?php

declare(strict_types=1);

namespace Src\Payment\Infrastructure\Http\Controller;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Src\Payment\Application\UseCase\UpdateCentralPaymentSettingsUseCase;
use Src\Shared\Helper\ApiResponse;

final class AdminUpdateCentralPaymentSettingsPUTController extends Controller
{
    public function __construct(
        private readonly UpdateCentralPaymentSettingsUseCase $useCase
    ) {}

    public function __invoke(Request $request): JsonResponse
    {
        $request->validate([
            'central_pago_movil_bank_name' => ['nullable', 'string', 'max:120'],
            'central_pago_movil_document_id' => ['nullable', 'string', 'max:40'],
            'central_pago_movil_phone' => ['nullable', 'string', 'max:40'],
            'central_pago_movil_holder_name' => ['nullable', 'string', 'max:150'],
            'central_binance_pay_id' => ['nullable', 'string', 'max:60'],
        ]);

        return ApiResponse::success(
            data: $this->useCase->execute($request->only(UpdateCentralPaymentSettingsUseCase::KEYS)),
            message: 'Datos de cobro actualizados.'
        );
    }
}
