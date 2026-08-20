<?php

declare(strict_types=1);

namespace Src\SupportTicket\Infrastructure\Http\Controller;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use Src\SupportTicket\Application\UseCase\ListUserSupportTicketsUseCase;

final class ViewTenantStoreSupportGETController extends Controller
{
    public function __construct(
        private readonly ListUserSupportTicketsUseCase $listTicketsUseCase
    ) {}

    public function __invoke(Request $request, string $user_uuid): Response
    {
        $tenantId = function_exists('tenant') && tenant() ? (string) tenant('id') : null;
        $status = $request->query('status');

        $ticketsData = $this->listTicketsUseCase->execute(
            $user_uuid,
            $status ? (string) $status : null,
            $tenantId
        );

        $storeName = function_exists('tenant') && tenant() ? (tenant('name') ?: ucfirst((string) tenant('slug'))) : 'Mi Tienda';

        return Inertia::render('tenant/support/TenantStoreSupportPage', [
            'title' => 'Soporte Técnico & Ayuda - '.$storeName,
            'user_id' => $user_uuid,
            'tenant_id' => $tenantId,
            'store_name' => $storeName,
            'tickets_data' => $ticketsData,
        ]);
    }
}
