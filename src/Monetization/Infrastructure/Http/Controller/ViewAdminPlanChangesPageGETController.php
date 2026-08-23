<?php

declare(strict_types=1);

namespace Src\Monetization\Infrastructure\Http\Controller;

use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use Src\Monetization\Application\UseCases\ListTenantPlanChangeRequestsUseCase;

/**
 * Pantalla de resolución de cambios de plan (hallazgo T3).
 *
 * Modelada sobre la de retiros, que es la misma clase de decisión: cambiar el plan cambia
 * la `commission_rate` de la tienda, o sea lo que la plataforma le cobra por cada venta.
 */
final class ViewAdminPlanChangesPageGETController
{
    public function __construct(
        private readonly ListTenantPlanChangeRequestsUseCase $useCase
    ) {}

    public function index(Request $request, string $user_uuid): Response
    {
        $status = $request->query('status', 'pending');
        $resultado = $this->useCase->execute(is_string($status) ? $status : 'pending');

        return Inertia::render('admin/planChanges/AdminPlanChangesIndexPage', [
            'title' => 'Cambios de Plan - OwOMarket Admin',
            'user_id' => $user_uuid,
            'requests' => $resultado['requests'],
            'metrics' => $resultado['metrics'],
            'filters' => ['status' => $status],
        ]);
    }
}
