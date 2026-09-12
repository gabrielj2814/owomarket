<?php

declare(strict_types=1);

namespace Src\Marketplace\Infrastructure\Http\Controller;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use Src\Category\Infrastructure\Eloquent\Models\Category;
use Src\TenantSettings\Application\UseCases\GetStoreSettingsUseCase;

/**
 * «Mis pedidos» en el escaparate de una tienda (fase 1 del plan del escaparate).
 *
 * Hasta ahora el comprador de una tienda no tenia ningun sitio donde ver lo que habia comprado,
 * asi que **no podia confirmar una entrega**: todas las ventas de escaparate se liberaban por
 * vencimiento del plazo.
 *
 * Los pedidos NO se pasan por props: los pide la pantalla a la API. La sesion del escaparate se
 * crea al canjear el token SSO, y una pantalla que los recibiera ya renderizados tendria que
 * volver a pedirlos igualmente tras confirmar una entrega --dos caminos hacia la misma lista, y
 * uno de ellos acabaria desfasado--.
 */
final class ViewStorefrontMyOrdersGETController extends Controller
{
    public function __construct(
        private readonly ?GetStoreSettingsUseCase $getStoreSettingsUseCase = null
    ) {}

    public function index(Request $request): Response
    {
        $storeSettings = [];
        try {
            $useCase = $this->getStoreSettingsUseCase ?? app(GetStoreSettingsUseCase::class);
            $storeSettings = $useCase->execute()->toKeyValueMap();
        } catch (\Throwable) {
            $storeSettings = [];
        }

        if (empty($storeSettings)) {
            $storeSettings = ['store_name' => 'Mi Tienda Online', 'currency' => 'USD'];
        }

        $categories = Category::query()
            ->where('is_active', true)
            ->orderBy('position', 'asc')
            ->get()
            ->map(fn (Category $cat) => [
                'id' => (string) $cat->id,
                'name' => $cat->name,
                'slug' => $cat->slug,
                'image' => $cat->image,
            ])
            ->all();

        return Inertia::render('marketplace/orders/StorefrontMyOrdersPage', [
            'domain' => $request->getHost(),
            'store_settings' => $storeSettings,
            'categories' => $categories,
        ]);
    }
}
