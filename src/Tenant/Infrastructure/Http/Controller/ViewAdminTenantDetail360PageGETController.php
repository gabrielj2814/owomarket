<?php

declare(strict_types=1);

namespace Src\Tenant\Infrastructure\Http\Controller;

use App\Http\Controllers\Controller;
use Exception;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use Src\Tenant\Application\UseCase\GetTenant360DetailUseCase;

final class ViewAdminTenantDetail360PageGETController extends Controller
{
    public function __construct(
        private readonly GetTenant360DetailUseCase $useCase
    ) {}

    public function __invoke(Request $request, string $user_uuid, string $id): Response
    {
        try {
            $data = $this->useCase->execute($id);

            return Inertia::render('admin/modules/tenants/AdminTenantDetail360Page', [
                'title' => "Expediente 360° Tienda {$data['tenant']->name} - OwOMarket",
                'user_id' => $user_uuid,
                'tenant' => $data['tenant'],
                'metrics' => $data['metrics'],
                'recent_orders' => $data['recent_orders'],
                'payouts' => $data['payouts'],
                'tickets' => $data['tickets'],
            ]);
        } catch (Exception $e) {
            abort(404, $e->getMessage());
        }
    }
}
