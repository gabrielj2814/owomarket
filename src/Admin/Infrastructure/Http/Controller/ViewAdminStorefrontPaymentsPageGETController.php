<?php

declare(strict_types=1);

namespace Src\Admin\Infrastructure\Http\Controller;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use Src\Admin\Application\UseCase\ListPendingStorefrontPaymentsUseCase;

/**
 * Fase 3c: la pantalla que faltaba.
 *
 * Los endpoints de la Fase 3a existian y no los llamaba nadie, asi que confirmar un cobro solo
 * se podia hacer por HTTP a mano. Y como el comerciante dejo de poder marcar sus pedidos como
 * pagados en la Fase 3b, sin esta pantalla no habia forma de destrabar una comision desde la
 * interfaz.
 */
final class ViewAdminStorefrontPaymentsPageGETController extends Controller
{
    public function __construct(
        private readonly ListPendingStorefrontPaymentsUseCase $useCase
    ) {}

    public function __invoke(Request $request, string $user_uuid): Response
    {
        $resultado = $this->useCase->execute([
            'tenant_id' => $request->query('tenant_id'),
            'search' => $request->query('search'),
            'per_page' => (int) $request->query('per_page', 20),
        ]);

        return Inertia::render('admin/payments/AdminStorefrontPaymentsPage', [
            'title' => 'Cobros por Confirmar - OwOMarket',
            'user_id' => $user_uuid,
            'payments_data' => $resultado['payments'],
            'metrics' => $resultado['metrics'],
            'filters' => [
                'tenant_id' => $request->query('tenant_id', ''),
                'search' => $request->query('search', ''),
            ],
        ]);
    }
}
