<?php

declare(strict_types=1);

namespace Src\Payment\Infrastructure\Http\Controller;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use Src\Payment\Application\Service\CentralPaymentMethodsProvider;
use Src\Payment\Application\UseCase\UpdateCentralPaymentSettingsUseCase;

/**
 * Pantalla de datos de cobro de la plataforma (hallazgo N33).
 */
final class AdminViewCentralPaymentSettingsGETController extends Controller
{
    public function __construct(
        private readonly CentralPaymentMethodsProvider $paymentMethods
    ) {}

    public function __invoke(Request $request): Response
    {
        return Inertia::render('admin/payments/CentralPaymentSettingsPage', [
            'title' => 'Datos de Cobro de la Plataforma - OwOMarket',
            'user_id' => (string) ($request->route('user_uuid') ?? $request->user()?->id),
            'settings' => UpdateCentralPaymentSettingsUseCase::current(),
            // Se muestra lo mismo que vera el comprador, para que el superadmin pueda
            // comprobar de un vistazo si un metodo esta completo o no se ofrece.
            'active_methods' => $this->paymentMethods->all(),
        ]);
    }
}
