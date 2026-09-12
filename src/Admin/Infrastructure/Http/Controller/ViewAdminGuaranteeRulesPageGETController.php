<?php

declare(strict_types=1);

namespace Src\Admin\Infrastructure\Http\Controller;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use Src\CentralCustomer\Application\Service\ClaimResponseWindow;
use Src\CentralCustomer\Application\Service\ClaimWindow;
use Src\CentralCustomer\Application\Service\MonthlyCoverageSpend;
use Src\CentralCustomer\Application\UseCases\ResolveReturnRequestUseCase;
use Src\Monetization\Application\Service\TenantAvailableBalance;
use Src\Monetization\Application\UseCases\ReleaseOrderCommissionUseCase;
use Src\Monetization\Application\UseCases\ReleaseUnconfirmedDeliveriesUseCase;
use Src\Payment\Application\UseCase\UpdateCentralPaymentSettingsUseCase;

/**
 * Las reglas del dinero en tránsito (subsistemas 3, 4 y 5).
 *
 * ## Por qué es una pantalla y no un apartado de «Datos de cobro»
 *
 * Aquella responde a *«a qué cuenta transfiere el comprador»*. Estas ocho responden a otra
 * pregunta —cuándo cobra el comerciante, cuánto se aparta y hasta cuándo se puede reclamar— y
 * juntarlas dejaría una página que no sabe de qué trata.
 *
 * ## Lo que arregla
 *
 * Los ocho ajustes ya estaban en la lista blanca del backend y **ninguno tenía campo en
 * ninguna pantalla**. Todos los números que gobiernan el dinero se cambiaban escribiendo en la
 * base de datos a mano, sin validación y sin rastro de quién los tocó.
 *
 * El caso peor es `central_guarantee_reserve_percent`: con solo existir esa fila, el sistema
 * de reputación deja de aplicarse y todas las tiendas retienen lo mismo. Una fila que alguien
 * dejó puesta probando aplanaba el subsistema 4 **y no había dónde verlo**.
 *
 * Se manda al mismo endpoint que los datos de cobro: `UpdateCentralPaymentSettingsUseCase` ya
 * los tenía en su lista blanca y se salta las claves que no llegan, así que un envío parcial
 * funciona. Dos listas blancas de lo mismo acabarían divergiendo.
 */
final class ViewAdminGuaranteeRulesPageGETController extends Controller
{
    public function __construct(
        private readonly MonthlyCoverageSpend $cobertura
    ) {}

    public function __invoke(Request $request): Response
    {
        return Inertia::render('admin/guarantee/AdminGuaranteeRulesPage', [
            'title' => 'Reglas de garantía - OwOMarket',
            'user_id' => (string) ($request->route('user_uuid') ?? $request->user()?->id),
            'settings' => UpdateCentralPaymentSettingsUseCase::current(),
            /*
             * Los valores por defecto viajan al navegador para poder enseñarlos como
             * marcador de posición. Es lo que convierte «vacío» en información en vez de en
             * duda: se ve qué está tocado a mano y qué corre con lo que dice el código.
             *
             * Salen de la constante de cada clase, no copiados aquí. Un marcador que miente
             * --porque alguien cambió el defecto y olvidó esta lista-- es peor que no tener
             * ninguno: convence de algo falso en vez de dejar con la duda.
             */
            'defaults' => [
                'central_delivery_confirmation_days' => (string) ReleaseUnconfirmedDeliveriesUseCase::DIAS_POR_DEFECTO,
                'central_payout_hold_days' => (string) TenantAvailableBalance::DIAS_DE_RETENCION_POR_DEFECTO,
                // El único que no es un número: vacío significa que manda la reputación de
                // cada tienda, no un valor fijo.
                'central_guarantee_reserve_percent' => 'según reputación',
                'central_guarantee_reserve_days' => (string) ReleaseOrderCommissionUseCase::DIAS_POR_DEFECTO,
                'central_claim_window_days' => (string) ClaimWindow::DIAS_POR_DEFECTO,
                'central_claim_response_days' => (string) ClaimResponseWindow::DIAS_POR_DEFECTO,
                'central_claim_coverage_cap' => (string) ResolveReturnRequestUseCase::TOPE_POR_DEFECTO,
                'central_claim_monthly_alarm_usd' => (string) MonthlyCoverageSpend::TECHO_POR_DEFECTO,
            ],
            'coverage_months' => $this->cobertura->lastMonths(),
            'coverage_threshold' => $this->cobertura->threshold(),
        ]);
    }
}
