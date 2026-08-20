<?php

declare(strict_types=1);

namespace Src\Admin\Infrastructure\Http\Controller;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use Src\Admin\Application\UseCase\ListCentralOrdersForAdminUseCase;

final class ViewAdminGlobalOrdersPageGETController extends Controller
{
    public function __construct(
        private readonly ListCentralOrdersForAdminUseCase $useCase
    ) {}

    public function __invoke(Request $request, string $user_uuid): Response
    {
        $result = $this->useCase->execute([
            'tenant_id' => $request->query('tenant_id'),
            'status' => $request->query('status'),
            'payment_status' => $request->query('payment_status'),
            'search' => $request->query('search'),
            'date_from' => $request->query('date_from'),
            'date_to' => $request->query('date_to'),
            'per_page' => (int) ($request->query('per_page', 15)),
        ]);

        return Inertia::render('admin/orders/AdminGlobalOrdersPage', [
            'title' => 'Monitor Global de Órdenes & Disputas - OwOMarket',
            'user_id' => $user_uuid,
            'orders_data' => $result['orders'],
            'metrics' => $result['metrics'],
            'tenants_list' => $result['tenants'],
            'filters' => [
                'tenant_id' => $request->query('tenant_id', ''),
                'status' => $request->query('status', ''),
                'payment_status' => $request->query('payment_status', ''),
                'search' => $request->query('search', ''),
                'date_from' => $request->query('date_from', ''),
                'date_to' => $request->query('date_to', ''),
            ],
        ]);
    }
}
