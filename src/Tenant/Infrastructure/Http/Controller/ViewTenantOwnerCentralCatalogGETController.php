<?php

declare(strict_types=1);

namespace Src\Tenant\Infrastructure\Http\Controller;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use Src\Tenant\Application\UseCase\ListTenantOwnerProductsUseCase;

final class ViewTenantOwnerCentralCatalogGETController extends Controller
{
    public function __construct(
        private readonly ListTenantOwnerProductsUseCase $listProductsUseCase
    ) {}

    public function __invoke(Request $request, string $user_uuid): Response
    {
        $tenantId = $request->query('tenant_id');
        $search = $request->query('search');

        $catalog = $this->listProductsUseCase->execute($user_uuid, $tenantId ? (string) $tenantId : null, $search ? (string) $search : null);

        return Inertia::render('tenant/catalog/TenantOwnerCentralCatalogPage', [
            'title' => 'Publicador de Catálogo Central - OwOMarket',
            'user_id' => $user_uuid,
            'catalog' => $catalog,
        ]);
    }
}
