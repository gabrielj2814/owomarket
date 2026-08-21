<?php

declare(strict_types=1);

namespace Src\Marketplace\Infrastructure\Http\Controller;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use Src\Category\Infrastructure\Eloquent\Models\Category;
use Src\Payment\Application\Service\StorefrontPaymentMethodsProvider;
use Src\TenantSettings\Application\UseCases\GetStoreSettingsUseCase;

final class ViewCheckoutTenantGETController extends Controller
{
    public function __construct(
        private readonly ?GetStoreSettingsUseCase $getStoreSettingsUseCase = null,
        private readonly ?StorefrontPaymentMethodsProvider $paymentMethodsProvider = null
    ) {}

    public function index(Request $request): Response
    {
        $host = $request->getHost();

        // 1. Fetch Store Settings
        $storeSettings = [];
        try {
            $useCase = $this->getStoreSettingsUseCase ?? app(GetStoreSettingsUseCase::class);
            $settingsEntity = $useCase->execute();
            $storeSettings = $settingsEntity->toKeyValueMap();
        } catch (\Throwable) {
            $storeSettings = [
                'store_name' => 'Mi Tienda Online',
                'currency' => 'USD',
            ];
        }

        if (empty($storeSettings)) {
            $storeSettings = [
                'store_name' => 'Mi Tienda Online',
                'currency' => 'USD',
            ];
        }

        // 2. Fetch Active Categories for Navbar
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

        // 3. Available Shipping Methods
        $shippingMethods = [
            [
                'id' => 'standard',
                'title' => 'Envío Estándar a Domicilio',
                'description' => 'Entrega estimada en 3 a 5 días hábiles con seguimiento',
                'price' => 5.00,
            ],
            [
                'id' => 'express',
                'title' => 'Envío Prioritario Express',
                'description' => 'Entrega rápida en 24 a 48 horas',
                'price' => 9.00,
            ],
            [
                'id' => 'pickup',
                'title' => 'Retiro en Tienda / Sucursal',
                'description' => 'Disponible para retiro en horario de atención sin costo',
                'price' => 0.00,
            ],
        ];

        // 4. Available Payment Methods
        //
        // Hallazgo G1: esta lista estaba hardcodeada con datos de demostración
        // —RIF 'J-50123456-0', Binance Pay ID '284759302', un QR servido por
        // api.qrserver.com y una tasa de 40,50 Bs/USD cuando la real ronda
        // 775—. El comprador transfería el dinero a una cuenta que no era la
        // de la tienda.
        //
        // Ahora se construye desde los datos que el comerciante haya
        // configurado (grupo de settings `payment`). Un método sin configurar
        // NO se ofrece: mejor una opción menos que un cobro a un tercero.
        $provider = $this->paymentMethodsProvider ?? app(StorefrontPaymentMethodsProvider::class);
        $paymentMethods = $provider->forStore($storeSettings);

        // 5. Auth User
        $authUser = null;
        if (auth()->check()) {
            $user = auth()->user();
            $authUser = [
                'id' => (string) $user->id,
                'name' => $user->name,
                'email' => $user->email,
            ];
        }

        return Inertia::render('marketplace/checkout/TenantCheckoutPage', [
            'domain' => $host,
            'store_settings' => $storeSettings,
            'categories' => $categories,
            'shipping_methods' => $shippingMethods,
            'payment_methods' => $paymentMethods,
            'auth_user' => $authUser,
        ]);
    }
}
