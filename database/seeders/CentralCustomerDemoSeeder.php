<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\CentralCustomer;
use App\Models\CentralCustomerAddress;
use App\Models\CentralCustomerWishlist;
use App\Models\CentralOrder;
use App\Models\CentralOrderItem;
use App\Models\CustomerReturnRequest;
use Database\Seeders\Concerns\RunsOnlyInDevelopment;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;

final class CentralCustomerDemoSeeder extends Seeder
{
    use RunsOnlyInDevelopment;

    public function run(): void
    {
        if ($this->shouldSkipOutsideDevelopment()) {
            return;
        }

        $conn = app()->environment('testing') ? config('database.default') : 'central';

        if (! Schema::connection($conn)->hasTable('central_customers')) {
            return;
        }

        // 1. Crear o Actualizar el Cliente Demo
        $customer = CentralCustomer::updateOrCreate(
            ['email' => 'cliente@owomarket.local'],
            [
                'name' => 'Carlos Mendoza',
                'phone' => '+58 412 1234567',
                'password' => Hash::make('Password123!'),
                'document_id' => 'V-24890123',
                'avatar' => 'https://images.unsplash.com/photo-1535713875002-d1d0cf377fde?w=150&h=150&fit=crop',
                'is_active' => true,
                'email_verified_at' => now(),
            ]
        );

        // 2. Libreta de Direcciones (Casa y Oficina)
        if (Schema::connection($conn)->hasTable('central_customer_addresses')) {
            CentralCustomerAddress::updateOrCreate(
                [
                    'customer_id' => $customer->id,
                    'label' => 'Casa (Chacao)',
                ],
                [
                    'address' => 'Av. Francisco de Miranda, Edif. Torre Centro, Apto 4B, Chacao',
                    'city' => 'Caracas',
                    'state' => 'Miranda',
                    'zip_code' => '1060',
                    'country' => 'VE',
                    'is_default' => true,
                ]
            );

            CentralCustomerAddress::updateOrCreate(
                [
                    'customer_id' => $customer->id,
                    'label' => 'Oficina (Las Mercedes)',
                ],
                [
                    'address' => 'Av. Principal de Las Mercedes, Torre Las Mercedes, Piso 6, Baruta',
                    'city' => 'Caracas',
                    'state' => 'Miranda',
                    'zip_code' => '1080',
                    'country' => 'VE',
                    'is_default' => false,
                ]
            );
        }

        // 3. Pedidos Demo (Uno Entregado y Uno en Camino)
        if (Schema::connection($conn)->hasTable('central_orders') && Schema::connection($conn)->hasTable('central_order_items')) {
            $tenant = \Src\Tenant\Infrastructure\Eloquent\Models\Tenant::first();
            if (! $tenant) {
                $tenant = \Src\Tenant\Infrastructure\Eloquent\Models\Tenant::create([
                    'id' => 'tecs',
                    'name' => 'TECS Store',
                    'slug' => 'tecs',
                    'status' => 'active',
                    'request' => 'approved',
                ]);
            }
            $tenantId = (string) $tenant->id;

            // Pedido 1: Entregado con Pago Móvil
            $order1 = CentralOrder::updateOrCreate(
                ['order_number' => 'ORD-2026-DEMO01'],
                [
                    'customer_id' => $customer->id,
                    'customer_name' => $customer->name,
                    'customer_email' => $customer->email,
                    'customer_phone' => $customer->phone,
                    'customer_document_id' => $customer->document_id,
                    'shipping_address' => [
                        'label' => 'Casa (Chacao)',
                        'address' => 'Av. Francisco de Miranda, Edif. Torre Centro, Apto 4B, Chacao',
                        'city' => 'Caracas',
                        'state' => 'Miranda',
                    ],
                    'payment_method' => 'pago_movil',
                    'payment_details' => [
                        'bank_origin' => 'Banesco (0134)',
                        'phone_origin' => '04121234567',
                        'reference_number' => '98765432',
                        'rate_bcv' => 45.50,
                        'total_bs' => 45.50 * 120.00,
                    ],
                    'subtotal' => 120.00,
                    'discount_amount' => 10.00,
                    'shipping_amount' => 0.00,
                    'total' => 110.00,
                    'currency' => 'USD',
                    'status' => 'completed',
                    'payment_status' => 'paid',
                    'created_at' => now()->subDays(5),
                    'updated_at' => now()->subDays(2),
                ]
            );

            CentralOrderItem::updateOrCreate(
                [
                    'central_order_id' => $order1->id,
                    'product_name' => 'Audífonos Inalámbricos Bluetooth Noise Cancelling',
                ],
                [
                    'tenant_id' => $tenantId,
                    'product_id' => 'prod-audio-01',
                    'sku' => 'AUD-BT-PRO',
                    'price' => 70.00,
                    'quantity' => 1,
                    'total' => 70.00,
                ]
            );

            CentralOrderItem::updateOrCreate(
                [
                    'central_order_id' => $order1->id,
                    'product_name' => 'Power Bank 20000mAh Carga Rápida 65W',
                ],
                [
                    'tenant_id' => $tenantId,
                    'product_id' => 'prod-power-02',
                    'sku' => 'PB-65W-20K',
                    'price' => 50.00,
                    'quantity' => 1,
                    'total' => 50.00,
                ]
            );

            // Pedido 2: En Camino con Binance Pay
            $order2 = CentralOrder::updateOrCreate(
                ['order_number' => 'ORD-2026-DEMO02'],
                [
                    'customer_id' => $customer->id,
                    'customer_name' => $customer->name,
                    'customer_email' => $customer->email,
                    'customer_phone' => $customer->phone,
                    'customer_document_id' => $customer->document_id,
                    'shipping_address' => [
                        'label' => 'Casa (Chacao)',
                        'address' => 'Av. Francisco de Miranda, Edif. Torre Centro, Apto 4B, Chacao',
                        'city' => 'Caracas',
                        'state' => 'Miranda',
                    ],
                    'payment_method' => 'binance_pay',
                    'payment_details' => [
                        'binance_id' => 'BIN-892187391',
                        'transaction_hash' => '0x8f1982bca819283719a82b9c',
                    ],
                    'subtotal' => 85.00,
                    'discount_amount' => 5.00,
                    'shipping_amount' => 0.00,
                    'total' => 80.00,
                    'currency' => 'USD',
                    'status' => 'processing',
                    'payment_status' => 'paid',
                    'created_at' => now()->subDay(),
                    'updated_at' => now()->subHours(6),
                ]
            );

            CentralOrderItem::updateOrCreate(
                [
                    'central_order_id' => $order2->id,
                    'product_name' => 'Mouse Gamer Ergonómico RGB 16000 DPI',
                ],
                [
                    'tenant_id' => $tenantId,
                    'product_id' => 'prod-mouse-03',
                    'sku' => 'MOU-RGB-16K',
                    'price' => 45.00,
                    'quantity' => 1,
                    'total' => 45.00,
                ]
            );

            CentralOrderItem::updateOrCreate(
                [
                    'central_order_id' => $order2->id,
                    'product_name' => 'Mousepad XXL Antideslizante 900x400mm',
                ],
                [
                    'tenant_id' => $tenantId,
                    'product_id' => 'prod-pad-04',
                    'sku' => 'PAD-XXL-BLK',
                    'price' => 40.00,
                    'quantity' => 1,
                    'total' => 40.00,
                ]
            );
        }

        // 4. Lista de Favoritos Demo (Wishlist)
        if (Schema::connection($conn)->hasTable('central_customer_wishlists')) {
            $favTenantId = isset($tenantId) ? $tenantId : 'tecs';
            CentralCustomerWishlist::updateOrCreate(
                [
                    'customer_id' => $customer->id,
                    'product_id' => 'fav-prod-01',
                    'tenant_id' => $favTenantId,
                ],
                [
                    'product_name' => 'Monitor Gamer 27" Curvo 165Hz IPS QHD',
                    'product_slug' => 'monitor-gamer-27-curvo-165hz',
                    'product_price' => 280.00,
                    'product_image' => 'https://images.unsplash.com/photo-1527443224154-c4a3942d3acf?w=400&fit=crop',
                ]
            );

            CentralCustomerWishlist::updateOrCreate(
                [
                    'customer_id' => $customer->id,
                    'product_id' => 'fav-prod-02',
                    'tenant_id' => $favTenantId,
                ],
                [
                    'product_name' => 'Chaqueta Deportiva Impermeable Windbreaker',
                    'product_slug' => 'chaqueta-deportiva-impermeable',
                    'product_price' => 45.00,
                    'product_image' => 'https://images.unsplash.com/photo-1544441893-675973e31985?w=400&fit=crop',
                ]
            );
        }

        // 5. Solicitud de Devolución Demo (RMA)
        if (Schema::connection($conn)->hasTable('customer_return_requests') && isset($order1)) {
            $retTenantId = isset($tenantId) ? $tenantId : 'tecs';
            CustomerReturnRequest::updateOrCreate(
                [
                    'order_id' => $order1->id,
                    'product_id' => 'prod-audio-01',
                ],
                [
                    'order_number' => $order1->order_number,
                    'customer_id' => $customer->id,
                    'customer_email' => $customer->email,
                    'product_name' => 'Audífonos Inalámbricos Bluetooth Noise Cancelling',
                    'tenant_id' => $retTenantId,
                    'reason' => 'Defecto de fábrica',
                    'description' => 'El auricular izquierdo presenta distorsión intermitente. Solicito reemplazo de unidad.',
                    'photos' => ['https://images.unsplash.com/photo-1546435770-a3e426bf472b?w=400&fit=crop'],
                    'status' => 'in_review',
                    'admin_notes' => 'Recibido por soporte técnico. En proceso de verificación para envío de nueva unidad.',
                ]
            );
        }
    }
}
