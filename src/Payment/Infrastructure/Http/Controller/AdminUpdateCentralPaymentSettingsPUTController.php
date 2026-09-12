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

            /*
             * Las reglas de garantía. Hasta ahora estaban en la lista blanca del caso de uso
             * pero **no se validaban aquí**: `$request->only(KEYS)` las dejaba pasar tal cual,
             * así que un «abc» en el porcentaje del fondo se guardaba sin protestar.
             *
             * Los topes de arriba no son decorativos. Un 100% de retención congela el dinero
             * de todas las tiendas; 3650 días de ventana deja reclamable una compra de hace
             * diez años. Son valores que nadie escribe queriendo, y que solo se descubren
             * cuando alguien no cobra.
             */
            'central_delivery_confirmation_days' => ['nullable', 'integer', 'min:1', 'max:90'],
            'central_payout_hold_days' => ['nullable', 'integer', 'min:0', 'max:365'],
            'central_guarantee_reserve_percent' => ['nullable', 'numeric', 'min:0', 'max:100'],
            'central_guarantee_reserve_days' => ['nullable', 'integer', 'min:0', 'max:365'],
            'central_claim_window_days' => ['nullable', 'integer', 'min:1', 'max:365'],
            'central_claim_response_days' => ['nullable', 'integer', 'min:1', 'max:90'],
            'central_claim_coverage_cap' => ['nullable', 'numeric', 'min:0'],
            'central_claim_monthly_alarm_usd' => ['nullable', 'numeric', 'min:0'],
        ]);

        return ApiResponse::success(
            data: $this->useCase->execute($request->only(UpdateCentralPaymentSettingsUseCase::KEYS)),
            message: 'Datos de cobro actualizados.'
        );
    }
}
