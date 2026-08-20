<?php

declare(strict_types=1);

namespace Src\SupportTicket\Infrastructure\Http\Controller;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use Src\SupportTicket\Application\UseCase\ListUserSupportTicketsUseCase;
use Src\Tenant\Infrastructure\Eloquent\Models\Tenant;

final class ViewTenantOwnerSupportGETController extends Controller
{
    public function __construct(
        private readonly ListUserSupportTicketsUseCase $listTicketsUseCase
    ) {}

    public function __invoke(Request $request, string $user_uuid): Response
    {
        $status = $request->query('status');
        $tenantId = $request->query('tenant_id');

        $ticketsData = $this->listTicketsUseCase->execute(
            $user_uuid,
            $status ? (string) $status : null,
            $tenantId ? (string) $tenantId : null
        );

        $tenants = Tenant::whereHas('users', function ($q) use ($user_uuid) {
            $q->where('user_id', $user_uuid);
        })->get();

        if ($tenants->isEmpty()) {
            $tenants = Tenant::where('status', 'active')->limit(5)->get();
        }

        return Inertia::render('tenant/support/TenantOwnerSupportPage', [
            'title' => 'Centro de Soporte & Reporte de Incidencias - OwOMarket',
            'user_id' => $user_uuid,
            'tickets_data' => $ticketsData,
            'tenants' => $tenants->map(fn ($t) => [
                'id' => $t->id,
                'name' => $t->name ?? ucfirst($t->slug),
                'slug' => $t->slug,
            ])->toArray(),
        ]);
    }
}
