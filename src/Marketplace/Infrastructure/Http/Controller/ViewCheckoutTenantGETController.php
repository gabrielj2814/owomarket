<?php

declare(strict_types=1);

namespace Src\Marketplace\Infrastructure\Http\Controller;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use Src\Category\Infrastructure\Eloquent\Models\Category;
use Src\TenantSettings\Application\UseCases\GetStoreSettingsUseCase;

final class ViewCheckoutTenantGETController extends Controller
{
    public function __construct(
        private readonly ?GetStoreSettingsUseCase $getStoreSettingsUseCase = null
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
        $paymentMethods = [
            [
                'id' => 'pago_movil',
                'name' => 'Pago Móvil Interbancario (VES)',
                'description' => 'Transfiere en Bolívares (VES) al instante a través de Pago Móvil e ingresa el número de referencia.',
                'bank_name' => '0102 - Banco de Venezuela',
                'phone' => $storeSettings['contact_phone'] ?? '0412-1234567',
                'document_id' => 'J-50123456-0',
                'holder_name' => $storeSettings['store_name'] ?? 'OwOMarket Store',
                'exchange_rate_ves' => 40.50,
            ],
            [
                'id' => 'binance_pay',
                'name' => 'Binance Pay / USDT (Cripto)',
                'description' => 'Pago instantáneo en USDT sin comisiones de red usando Binance Pay ID o escaneando el código QR.',
                'binance_pay_id' => '284759302',
                'crypto_currency' => 'USDT',
                'qr_code' => 'https://api.qrserver.com/v1/create-qr-code/?size=200x200&data=binancepay://pay?id=284759302',
            ],
            [
                'id' => 'bank_transfer',
                'name' => 'Transferencia Bancaria Directa',
                'description' => 'Realiza tu pago directamente en nuestra cuenta bancaria. Tu pedido será procesado tras verificar el comprobante.',
                'instructions' => 'Banco: Banco de Venezuela / Mercantil | Tipo: Cuenta Corriente | Titular: '.($storeSettings['store_name'] ?? 'Comercio').' | Email: '.($storeSettings['store_email'] ?? 'pagos@tienda.com'),
            ],
            [
                'id' => 'cash_on_delivery',
                'name' => 'Pago Contra Entrega / Efectivo',
                'description' => 'Paga en efectivo al momento de recibir tu paquete en tu domicilio.',
            ],
        ];

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
