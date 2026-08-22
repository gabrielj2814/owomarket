<?php

declare(strict_types=1);

namespace Src\Marketplace\Infrastructure\Http\Controller;

use App\Http\Controllers\Controller;
use Inertia\Response;
use Src\Payment\Application\Service\CentralPaymentMethodsProvider;

final class ViewCheckoutCentralGETController extends Controller
{
    public function __construct(
        private readonly CentralPaymentMethodsProvider $paymentMethods
    ) {}

    public function index(): Response
    {
        return inertia()->render('marketplace/checkout/CentralCheckoutPage', [
            'domain' => request()->getHost(),
            // La Fase 0.5 saco los datos de cobro de demostracion del checkout del
            // inquilino, pero el central se quedo con los suyos incrustados en el TSX.
            // Ahora salen de `central_settings`, y un metodo sin configurar no se ofrece.
            'payment_methods' => $this->paymentMethods->all(),
        ]);
    }
}
