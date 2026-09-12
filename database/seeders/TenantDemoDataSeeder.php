<?php

declare(strict_types=1);

namespace Database\Seeders;

use Database\Seeders\Concerns\RunsOnlyInDevelopment;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;
use Src\Attribute\Infrastructure\Eloquent\Models\ProductAttribute;
use Src\Brand\Infrastructure\Eloquent\Models\Brand;
use Src\Category\Infrastructure\Eloquent\Models\Category;
use Src\Coupon\Infrastructure\Eloquent\Models\Coupon;
use Src\Customer\Infrastructure\Eloquent\Models\Customer;
use Src\Product\Infrastructure\Eloquent\Models\Product;
use Src\Product\Infrastructure\Eloquent\Models\ProductImage;
use Src\Product\Infrastructure\Eloquent\Models\ProductVariant;
use Src\Review\Infrastructure\Eloquent\Models\ProductReview;
use Src\Tenant\Infrastructure\Eloquent\Models\Tenant;
use Src\Tenant\Infrastructure\Eloquent\Models\TenantKycProfile;
use Src\TenantSettings\Infrastructure\Eloquent\Models\TenantSetting;

final class TenantDemoDataSeeder extends Seeder
{
    use RunsOnlyInDevelopment;

    public function run(): void
    {
        if ($this->shouldSkipOutsideDevelopment()) {
            return;
        }

        $tenants = Tenant::all();

        if ($tenants->isEmpty()) {
            $this->command->warn('No se encontraron tenants registrados. Ejecutando seeding en el contexto actual si aplica.');
            $this->seedTenantData();

            return;
        }

        foreach ($tenants as $tenant) {
            $this->command->info("🌱 Sembrando datos de demostración para el tenant: {$tenant->name} ({$tenant->slug})");

            // ANTES de entrar en el contexto del inquilino: el expediente de identidad vive en
            // la base CENTRAL, no en la de la tienda.
            $this->seedKycProfile($tenant);

            tenancy()->initialize($tenant);

            try {
                $this->seedTenantData($tenant->name);
            } finally {
                tenancy()->end();
            }
        }
    }

    /**
     * El expediente de identidad de una tienda de demostración, ya verificado (subsistema 1).
     *
     * **Sin esto un entorno recién sembrado no puede probar el retiro.** El KYC exige identidad
     * verificada para solicitar un pago, así que sin perfiles ninguna de las tiendas sembradas
     * puede cobrar y la mitad del flujo de dinero queda inalcanzable en desarrollo.
     *
     * Se siembra en `verified` a propósito: es dato de demostración, y dejarlo en `pending`
     * obligaría a pasar por el backoffice antes de poder probar nada. Quien quiera ejercitar la
     * pantalla de revisión tiene el botón de rechazar, que devuelve el expediente a un estado
     * revisable.
     *
     * Las cédulas son inventadas y el modelo las cifra al guardarlas, igual que en producción:
     * un seeder que escribiera en texto plano dejaría datos que no se parecen a los reales
     * justo en la columna donde la diferencia importa.
     */
    private function seedKycProfile(Tenant $tenant): void
    {
        if (TenantKycProfile::where('tenant_id', $tenant->id)->exists()) {
            return;
        }

        // El responsable es el dueño de la tienda. Si la tienda sembrada no tiene ninguno
        // asociado, el expediente se queda sin `user_id` -- es opcional -- en vez de inventar
        // una persona que no existe.
        $userId = $tenant->users()->first()?->id;

        $digitos = random_int(10_000_000, 29_999_999);

        TenantKycProfile::create([
            'id' => (string) Str::uuid(),
            'tenant_id' => $tenant->id,
            'user_id' => $userId,
            'legal_name' => $tenant->name.' C.A.',
            'cedula' => 'V-'.$digitos,
            'nationality' => 'V',
            'rif' => 'J-'.random_int(40_000_000, 49_999_999).'-'.random_int(0, 9),
            'phone' => '+58 412 '.random_int(1_000_000, 9_999_999),
            'address' => 'Av. Principal, Caracas, Venezuela',
            'status' => 'verified',
            'reviewed_at' => now(),
            'reviewed_by' => 'seeder',
        ]);
    }

    public function seedTenantData(?string $storeName = 'OwoStore'): void
    {
        // 0. Sincronizar Catálogo Maestro Central (Categorías y Marcas)
        try {
            app(\Src\Category\Application\UseCase\SyncCentralCategoriesUseCase::class)->execute();
            app(\Src\Brand\Application\UseCase\SyncCentralBrandsUseCase::class)->execute();
        } catch (\Throwable $e) {
            // Continuar si ocurre alguna inconsistencia aislada
        }

        // 1. Tenant Settings
        $settings = [
            'store_name' => $storeName.' Oficial',
            'store_email' => 'contacto@'.Str::slug($storeName).'.com',
            'currency' => 'USD',
            'contact_phone' => '+56 9 8765 4321',
            'address' => 'Av. Providencia 1370, Providencia, Santiago, Chile',
            'logo_url' => 'https://images.unsplash.com/photo-1542291026-7eec264c27ff?w=240&h=90&fit=crop',
            'banner_url' => 'https://images.unsplash.com/photo-1441986300917-64674bd600d8?w=1600&h=600&fit=crop',
            'social_facebook' => 'https://facebook.com/owostore',
            'social_instagram' => 'https://instagram.com/owostore',
            'social_whatsapp' => '+56987654321',
            'social_twitter' => 'https://twitter.com/owostore',
            'seo_title' => $storeName.' | Tu Tienda Online de Moda y Tecnología',
            'seo_description' => 'Encuentra las mejores marcas de calzado, indumentaria, accesorios y tecnología con despacho rápido y garantía asegurada.',
            'seo_keywords' => 'zapatillas, moda, ropa, tecnologia, smartphones, audio, ofertas',
        ];

        foreach ($settings as $key => $val) {
            TenantSetting::updateOrCreate(
                ['key' => $key],
                ['value' => $val]
            );
        }

        // 1.b Datos de cobro de la tienda (grupo `payment`).
        //
        // Fase 0.5 (hallazgo G1) sacó los datos bancarios de demostración que estaban
        // incrustados en el checkout y los movió a los settings del comerciante, con una
        // regla estricta: **un método de pago que no esté completamente configurado no se
        // ofrece**. Como ningún seeder llenaba el grupo `payment`, cada instalación nueva
        // dejaba todas las tiendas SIN NINGÚN método de pago en el checkout.
        //
        // Son datos de mentira, igual que el resto de este seeder, y sólo se cargan en
        // local y testing por la guarda de la Fase 2.1 (hallazgo F6).
        $paymentSettings = [
            'pago_movil_bank_name' => '0102 - Banco de Venezuela',
            'pago_movil_document_id' => 'J-40123456-7',
            'pago_movil_phone' => '0414-1234567',
            'pago_movil_holder_name' => $storeName.' C.A.',
            'binance_pay_id' => '123456789',
            'bank_transfer_instructions' => "Banco Mercantil
Cuenta corriente 0105-0000-00-0000000000
Titular: {$storeName} C.A.
RIF: J-40123456-7
Envía el comprobante por WhatsApp tras realizar la transferencia.",
            'cash_on_delivery_enabled' => '1',
        ];

        foreach ($paymentSettings as $key => $val) {
            TenantSetting::updateOrCreate(
                ['key' => $key],
                ['value' => $val, 'group' => 'payment']
            );
        }

        // 2. Categories
        $categoriesData = [
            ['name' => 'Calzado Urbano', 'slug' => 'calzado-urbano', 'position' => 1, 'image' => 'https://images.unsplash.com/photo-1549298916-b41d501d3772?w=100&h=100&fit=crop'],
            ['name' => 'Ropa y Moda', 'slug' => 'ropa-moda', 'position' => 2, 'image' => 'https://images.unsplash.com/photo-1523381210434-271e8be1f52b?w=100&h=100&fit=crop'],
            ['name' => 'Tecnología y Audio', 'slug' => 'tecnologia-audio', 'position' => 3, 'image' => 'https://images.unsplash.com/photo-1505740420928-5e560c06d30e?w=100&h=100&fit=crop'],
            ['name' => 'Accesorios y Relojes', 'slug' => 'accesorios-relojes', 'position' => 4, 'image' => 'https://images.unsplash.com/photo-1523275335684-37898b6baf30?w=100&h=100&fit=crop'],
            ['name' => 'Deportes y Outdoor', 'slug' => 'deportes-outdoor', 'position' => 5, 'image' => 'https://images.unsplash.com/photo-1517838277536-f5f99be501cd?w=100&h=100&fit=crop'],
        ];

        $categories = [];
        foreach ($categoriesData as $c) {
            $categories[$c['slug']] = Category::firstOrCreate(
                ['slug' => $c['slug']],
                [
                    'name' => $c['name'],
                    'slug' => $c['slug'],
                    'position' => $c['position'],
                    'image' => $c['image'],
                    'is_active' => true,
                ]
            );
        }

        // 3. Brands
        $brandsData = [
            ['name' => 'Nike', 'slug' => 'nike'],
            ['name' => 'Adidas', 'slug' => 'adidas'],
            ['name' => 'Sony', 'slug' => 'sony'],
            ['name' => 'Apple', 'slug' => 'apple'],
            ['name' => 'Puma', 'slug' => 'puma'],
        ];

        $brands = [];
        foreach ($brandsData as $b) {
            $brands[$b['slug']] = Brand::firstOrCreate(
                ['slug' => $b['slug']],
                [
                    'name' => $b['name'],
                    'slug' => $b['slug'],
                    'is_active' => true,
                ]
            );
        }

        // 4. Attributes
        $attrColor = ProductAttribute::firstOrCreate(
            ['slug' => 'color'],
            ['name' => 'Color', 'slug' => 'color', 'type' => 'color', 'is_visible' => true, 'is_filterable' => true]
        );

        $attrSize = ProductAttribute::firstOrCreate(
            ['slug' => 'talla'],
            ['name' => 'Talla', 'slug' => 'talla', 'type' => 'select', 'is_visible' => true, 'is_filterable' => true]
        );

        // 5. Products Setup
        $productsData = [
            [
                'name' => 'Zapatillas Running Nike Air Zoom Pegasus 40',
                'slug' => 'zapatillas-running-nike-air-zoom-pegasus-40',
                'sku' => 'NIKE-PEG40-001',
                'description' => "Una pisada elástica para cada carrera. La sensación familiar y solo para ti del Pegasus regresa para ayudarte a cumplir tus metas.\n\nEsta versión mantiene la responsividad y el soporte neutro que amas, pero con una comodidad mejorada en esas áreas sensibles del pie como el arco y los dedos.",
                'price' => 129.99,
                'compare_price' => 159.99,
                'quantity' => 45,
                'is_featured' => true,
                'is_visible' => true,
                'category' => 'calzado-urbano',
                'brand' => 'nike',
                'specifications' => ['Material' => 'Malla transpirable engineered mesh', 'Tipo de pisada' => 'Neutro', 'Peso aproximado' => '285g'],
                // Los mismos 6 meses que antes solo decia la ficha en texto, ahora como dato.
                'warranty_days' => 180,
                'images' => [
                    'https://images.unsplash.com/photo-1542291026-7eec264c27ff?w=800&h=800&fit=crop',
                    'https://images.unsplash.com/photo-1606107557195-0e29a4b5b4aa?w=800&h=800&fit=crop',
                    'https://images.unsplash.com/photo-1584735935682-2f2b69dff9d2?w=800&h=800&fit=crop',
                ],
                'variants' => [
                    ['sku' => 'NIKE-PEG40-BLK-40', 'price' => 129.99, 'quantity' => 15, 'attributes' => ['Color' => 'Negro/Rojo', 'Talla' => '40'], 'image' => 'https://images.unsplash.com/photo-1542291026-7eec264c27ff?w=800&h=800&fit=crop'],
                    ['sku' => 'NIKE-PEG40-WHT-41', 'price' => 129.99, 'quantity' => 20, 'attributes' => ['Color' => 'Blanco/Azul', 'Talla' => '41'], 'image' => 'https://images.unsplash.com/photo-1606107557195-0e29a4b5b4aa?w=800&h=800&fit=crop'],
                    ['sku' => 'NIKE-PEG40-RED-42', 'price' => 139.99, 'quantity' => 10, 'attributes' => ['Color' => 'Rojo Carmesí', 'Talla' => '42'], 'image' => 'https://images.unsplash.com/photo-1584735935682-2f2b69dff9d2?w=800&h=800&fit=crop'],
                ],
            ],
            [
                'name' => 'Auriculares Inalámbricos Sony WH-1000XM5 Noise Cancelling',
                'slug' => 'auriculares-sony-wh-1000xm5-noise-cancelling',
                'sku' => 'SONY-WH1000XM5',
                'description' => "Cancelación de ruido líder en la industria con dos procesadores y ocho micrófonos. Calidad de sonido excepcional con tecnología High-Resolution Audio inalámbrico.\n\nHasta 30 horas de duración de batería con carga rápida (3 min de carga = 3 horas de reproducción).",
                'price' => 349.99,
                'compare_price' => 399.99,
                'quantity' => 25,
                'is_featured' => true,
                'is_visible' => true,
                'category' => 'tecnologia-audio',
                'brand' => 'sony',
                'specifications' => ['Conectividad' => 'Bluetooth 5.2 / Jack 3.5mm', 'Batería' => '30 horas con ANC', 'Micrófonos' => '8 micrófonos beamforming', 'Peso' => '250g'],
                'images' => [
                    'https://images.unsplash.com/photo-1505740420928-5e560c06d30e?w=800&h=800&fit=crop',
                    'https://images.unsplash.com/photo-1583394838336-acd977736f90?w=800&h=800&fit=crop',
                ],
                'variants' => [
                    ['sku' => 'SONY-XM5-BLK', 'price' => 349.99, 'quantity' => 15, 'attributes' => ['Color' => 'Negro Mate'], 'image' => 'https://images.unsplash.com/photo-1505740420928-5e560c06d30e?w=800&h=800&fit=crop'],
                    ['sku' => 'SONY-XM5-SLV', 'price' => 349.99, 'quantity' => 10, 'attributes' => ['Color' => 'Plata Platino'], 'image' => 'https://images.unsplash.com/photo-1583394838336-acd977736f90?w=800&h=800&fit=crop'],
                ],
            ],
            [
                'name' => 'Smartwatch Apple Watch Series 9 GPS 45mm',
                'slug' => 'smartwatch-apple-watch-series-9-gps-45mm',
                'sku' => 'APPL-WATCH-S9-45',
                'description' => "El reloj inteligente más potente con el nuevo chip S9 SiP. Disfruta de la pantalla Retina siempre activa de hasta 2.000 nits y la innovadora interacción mediante el gesto de doble toque.\n\nSensores avanzados de salud: ECG, oxígeno en sangre, sensor de temperatura y detección de caídas.",
                'price' => 429.99,
                'compare_price' => 459.99,
                'quantity' => 18,
                'is_featured' => true,
                'is_visible' => true,
                'category' => 'accesorios-relojes',
                'brand' => 'apple',
                'specifications' => ['Caja' => 'Aluminio 45mm', 'Pantalla' => 'OLED Retina Always-On 2000 nits', 'Resistencia al agua' => '50 metros (WR50)', 'Sistema Operativo' => 'watchOS 10'],
                'images' => [
                    'https://images.unsplash.com/photo-1523275335684-37898b6baf30?w=800&h=800&fit=crop',
                    'https://images.unsplash.com/photo-1508685096489-7aacd43bd3b1?w=800&h=800&fit=crop',
                ],
                'variants' => [
                    ['sku' => 'APPL-S9-MIDNIGHT', 'price' => 429.99, 'quantity' => 10, 'attributes' => ['Color' => 'Medianoche', 'Talla' => '45mm'], 'image' => 'https://images.unsplash.com/photo-1523275335684-37898b6baf30?w=800&h=800&fit=crop'],
                    ['sku' => 'APPL-S9-STARLIGHT', 'price' => 429.99, 'quantity' => 8, 'attributes' => ['Color' => 'Blanco Estrella', 'Talla' => '45mm'], 'image' => 'https://images.unsplash.com/photo-1508685096489-7aacd43bd3b1?w=800&h=800&fit=crop'],
                ],
            ],
            [
                'name' => 'Chaqueta Cortaviento Adidas Terrex Multi Sport',
                'slug' => 'chaqueta-cortaviento-adidas-terrex-multi-sport',
                'sku' => 'ADIDAS-TERREX-JKT',
                'description' => "Protección ligera para climas ventosos y llovizna ligera. Tejido técnico impermeable con tecnología WIND.RDY y detalles reflectantes para visibilidad nocturna.\n\nSe pliega fácilmente dentro de su propio bolsillo.",
                'price' => 89.99,
                'compare_price' => 119.99,
                'quantity' => 30,
                'is_featured' => true,
                'is_visible' => true,
                'category' => 'ropa-moda',
                'brand' => 'adidas',
                'specifications' => ['Material' => '100% Poliéster reciclado Primegreen', 'Impermeabilidad' => 'Tratamiento DWR repelente al agua', 'Bolsillos' => '2 laterales con cierre'],
                'images' => [
                    'https://images.unsplash.com/photo-1523381210434-271e8be1f52b?w=800&h=800&fit=crop',
                ],
                'variants' => [
                    ['sku' => 'ADIDAS-JKT-M-BLU', 'price' => 89.99, 'quantity' => 15, 'attributes' => ['Color' => 'Azul Marino', 'Talla' => 'M']],
                    ['sku' => 'ADIDAS-JKT-L-BLK', 'price' => 89.99, 'quantity' => 15, 'attributes' => ['Color' => 'Negro', 'Talla' => 'L']],
                ],
            ],
            [
                'name' => 'Mochila Impermeable Urbana Antirrobo con Puerto USB',
                'slug' => 'mochila-impermeable-urbana-antirrobo-puerto-usb',
                'sku' => 'BAG-ANTITHEFT-01',
                'description' => 'Mochila ejecutiva ergonómica con compartimento acolchado para notebook de hasta 15.6 pulgadas, cremalleras ocultas antirrobo y puerto de carga USB integrado.',
                'price' => 39.99,
                'compare_price' => 54.99,
                'quantity' => 50,
                'is_featured' => false,
                'is_visible' => true,
                'category' => 'accesorios-relojes',
                'brand' => 'puma',
                'specifications' => ['Capacidad' => '25 Litros', 'Compartimento Laptop' => 'Hasta 15.6"', 'Material' => 'Oxford 900D impermeable'],
                'images' => [
                    'https://images.unsplash.com/photo-1553062407-98eeb64c6a62?w=800&h=800&fit=crop',
                ],
                'variants' => [],
            ],
            [
                'name' => 'Gafas de Sol Polarizadas Estilo Aviador Classic',
                'slug' => 'gafas-sol-polarizadas-estilo-aviador-classic',
                'sku' => 'SUN-AVIATOR-UV400',
                'description' => 'Lentes polarizadas de alta definición con protección UV400 que bloquean el 100% de los rayos UVA y UVB. Marco metálico ultra liviano con almohadillas nasales de silicona suave.',
                'price' => 29.99,
                'compare_price' => 45.00,
                'quantity' => 40,
                'is_featured' => false,
                'is_visible' => true,
                'category' => 'accesorios-relojes',
                'brand' => 'puma',
                'specifications' => ['Protección' => 'UV400 Polarizado Categoría 3', 'Material Marco' => 'Aleación de magnesio y aluminio'],
                'images' => [
                    'https://images.unsplash.com/photo-1511499767150-a48a237f0083?w=800&h=800&fit=crop',
                ],
                'variants' => [],
            ],
        ];

        $createdProducts = [];
        foreach ($productsData as $pData) {
            $cat = $categories[$pData['category']] ?? null;
            $brnd = $brands[$pData['brand']] ?? null;

            $product = Product::firstOrCreate(
                ['slug' => $pData['slug']],
                [
                    'name' => $pData['name'],
                    'slug' => $pData['slug'],
                    'sku' => $pData['sku'],
                    'description' => $pData['description'],
                    'price' => $pData['price'],
                    'compare_price' => $pData['compare_price'] ?? null,
                    'quantity' => $pData['quantity'],
                    'is_featured' => $pData['is_featured'],
                    'is_visible' => $pData['is_visible'],
                    'category_id' => $cat?->id,
                    'brand_id' => $brnd?->id,
                    'specifications' => $pData['specifications'],
                    // Sin esto el marketplace central nace VACIO: el observador de producto
                    // solo sincroniza lo publicado, asi que un entorno recien sembrado tenia
                    // nueve tiendas con productos y `central_products` a cero. El checkout
                    // central --el flujo que monetiza la plataforma-- no se podia ni probar.
                    'is_published_central' => true,
                    // Subsistema 2. La demo ya prometia garantia, pero como texto suelto
                    // dentro de `specifications`; aqui va el dato con el que el subsistema 4
                    // calculara la retencion.
                    'warranty_days' => $pData['warranty_days'] ?? null,
                ]
            );

            // Images
            foreach ($pData['images'] as $order => $imgUrl) {
                ProductImage::firstOrCreate(
                    ['product_id' => $product->id, 'image_path' => $imgUrl],
                    ['order' => $order, 'is_default' => $order === 0]
                );
            }

            // Variants
            if (! empty($pData['variants'])) {
                foreach ($pData['variants'] as $vData) {
                    ProductVariant::firstOrCreate(
                        ['product_id' => $product->id, 'sku' => $vData['sku']],
                        [
                            'price' => $vData['price'],
                            'compare_price' => $pData['compare_price'] ?? null,
                            'quantity' => $vData['quantity'],
                            'attributes' => $vData['attributes'],
                            'image' => $vData['image'] ?? null,
                        ]
                    );
                }
            }

            $createdProducts[$pData['slug']] = $product;
        }

        // 6. Customers
        $customer1 = Customer::firstOrCreate(
            ['email' => 'maria.gonzalez@example.com'],
            ['name' => 'María González', 'phone' => '+56911223344', 'is_active' => true]
        );
        $customer2 = Customer::firstOrCreate(
            ['email' => 'sebastian.soto@example.com'],
            ['name' => 'Sebastián Soto', 'phone' => '+56922334455', 'is_active' => true]
        );
        $customer3 = Customer::firstOrCreate(
            ['email' => 'camila.silva@example.com'],
            ['name' => 'Camila Silva', 'phone' => '+56933445566', 'is_active' => true]
        );

        // 7. Product Reviews
        $nike = $createdProducts['zapatillas-running-nike-air-zoom-pegasus-40'] ?? null;
        if ($nike) {
            ProductReview::firstOrCreate(
                ['product_id' => $nike->id, 'customer_id' => $customer1->id],
                [
                    'rating' => 5,
                    'title' => '¡Increíble amortiguación y diseño!',
                    'comment' => 'Las zapatillas llegaron en perfectas condiciones y antes del tiempo estimado. Son comodísimas para trotar 10K diarios.',
                    'response' => '¡Muchas gracias María por tu preferencia! Que las disfrutes al máximo en tus entrenamientos.',
                    'responded_at' => now()->subDays(1),
                    'is_approved' => true,
                    'is_verified' => true,
                ]
            );

            ProductReview::firstOrCreate(
                ['product_id' => $nike->id, 'customer_id' => $customer2->id],
                [
                    'rating' => 5,
                    'title' => 'Excelente calce y peso pluma',
                    'comment' => 'La talla 41 calza exacta. Muy buena tracción en asfalto húmedo.',
                    'is_approved' => true,
                    'is_verified' => true,
                ]
            );
        }

        $sony = $createdProducts['auriculares-sony-wh-1000xm5-noise-cancelling'] ?? null;
        if ($sony) {
            ProductReview::firstOrCreate(
                ['product_id' => $sony->id, 'customer_id' => $customer3->id],
                [
                    'rating' => 5,
                    'title' => 'La mejor cancelación de ruido del mercado',
                    'comment' => 'Aislación acústica insuperable para trabajar y viajar en avión. La calidad de sonido en Hi-Res es brutal.',
                    'response' => '¡Nos alegra muchísimo que disfrutes la experiencia sonora de los Sony WH-1000XM5!',
                    'responded_at' => now()->subDays(2),
                    'is_approved' => true,
                    'is_verified' => true,
                ]
            );
        }

        // 8. Coupons
        $coupons = [
            [
                'code' => 'BIENVENIDA10',
                'type' => 'percentage',
                'value' => 10.00,
                'min_order_amount' => 20.00,
                'usage_limit' => 100,
                'valid_from' => now()->subDays(10)->toDateString(),
                'valid_to' => now()->addMonths(6)->toDateString(),
                'is_active' => true,
            ],
            [
                'code' => 'PROMO20',
                'type' => 'percentage',
                'value' => 20.00,
                'min_order_amount' => 50.00,
                'usage_limit' => 50,
                'valid_from' => now()->subDays(5)->toDateString(),
                'valid_to' => now()->addMonths(3)->toDateString(),
                'is_active' => true,
            ],
            [
                'code' => 'ENVIOGRATIS',
                'type' => 'fixed_amount',
                'value' => 5.00,
                'min_order_amount' => 30.00,
                'usage_limit' => 200,
                'valid_from' => now()->subDays(2)->toDateString(),
                'valid_to' => now()->addMonths(2)->toDateString(),
                'is_active' => true,
            ],
            [
                'code' => 'VIP30',
                'type' => 'percentage',
                'value' => 30.00,
                'min_order_amount' => 100.00,
                'usage_limit' => 20,
                'valid_from' => now()->subDays(1)->toDateString(),
                'valid_to' => now()->addMonths(1)->toDateString(),
                'is_active' => true,
            ],
        ];

        foreach ($coupons as $cData) {
            Coupon::firstOrCreate(
                ['code' => $cData['code']],
                $cData
            );
        }
    }
}
