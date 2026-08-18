<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Src\Customer\Domain\Entities\Customer;
use Src\Customer\Infrastructure\Eloquent\Repositories\EloquentCustomerRepository;
use Src\Order\Application\DTOs\FilterOrdersCriteria;
use Src\Order\Domain\Entities\Order;
use Src\Order\Domain\Entities\OrderItem;
use Src\Order\Domain\ValueObjects\OrderNumber;
use Src\Order\Infrastructure\Eloquent\Repositories\EloquentOrderRepository;
use Src\Product\Domain\Entities\Product;
use Src\Product\Infrastructure\Eloquent\Repositories\ProductRepository;
use Src\Tenant\Infrastructure\Eloquent\Models\Tenant as ModelsTenant;
use Stancl\Tenancy\Bootstrappers\DatabaseTenancyBootstrapper;
use Stancl\Tenancy\Events\TenantCreated;
use Stancl\Tenancy\Events\TenantDeleted;

beforeEach(function () {
    Event::fake([TenantCreated::class, TenantDeleted::class]);

    config([
        'tenancy.bootstrappers' => array_values(array_filter(
            config('tenancy.bootstrappers', []),
            fn ($bootstrapper) => $bootstrapper !== DatabaseTenancyBootstrapper::class
        )),
    ]);

    if (! Schema::hasTable('customers')) {
        (require base_path('database/migrations/tenant/2025_10_28_144201_create_customers.php'))->up();
    }
    if (! Schema::hasTable('addresses')) {
        (require base_path('database/migrations/tenant/2025_10_28_144231_create_addresses.php'))->up();
    }
    if (! Schema::hasTable('categories')) {
        (require base_path('database/migrations/tenant/2025_10_28_142911_create_categories.php'))->up();
    }
    if (! Schema::hasTable('brands')) {
        (require base_path('database/migrations/tenant/2025_10_28_143000_create_brands.php'))->up();
    }
    if (! Schema::hasTable('products')) {
        (require base_path('database/migrations/tenant/2025_10_28_143038_create_products.php'))->up();
    }
    if (! Schema::hasTable('product_variants')) {
        (require base_path('database/migrations/tenant/2025_10_28_143954_create_product_variants.php'))->up();
    }
    if (! Schema::hasTable('orders')) {
        (require base_path('database/migrations/tenant/2025_10_28_144320_create_orders.php'))->up();
    }
    if (! Schema::hasTable('order_items')) {
        (require base_path('database/migrations/tenant/2025_10_28_144403_create_order_items.php'))->up();
    }

    $tenantId = 't_'.bin2hex(random_bytes(4));
    $this->tenant = ModelsTenant::create([
        'id' => $tenantId,
        'name' => 'Order Test Store',
        'slug' => $tenantId,
        'status' => 'active',
        'request' => 'approved',
    ]);
    $this->domain = "{$tenantId}.localhost";
    $this->tenant->domains()->create([
        'id' => (string) Str::uuid(),
        'domain' => $this->domain,
    ]);

    tenancy()->initialize($this->tenant);
    $this->orderRepository = new EloquentOrderRepository;
    $this->customerRepository = new EloquentCustomerRepository;
    $this->productRepository = new ProductRepository;
});

afterEach(function () {
    if (tenancy()->initialized) {
        tenancy()->end();
    }
});

it('saves and retrieves an order with items in tenant database', function () {
    // 1. Create a Customer
    $customer = Customer::create(
        name: 'Carlos Santana',
        email: 'carlos@santana.cl'
    );
    $this->customerRepository->save($customer);

    // 2. Create a Product
    $product = \Src\Product\Infrastructure\Eloquent\Models\Product::create([
        'id' => (string) Str::uuid(),
        'name' => 'Guitarra Eléctrica PRS',
        'slug' => 'guitarra-electrica-prs',
        'sku' => 'PRS-SE-01',
        'price' => 999.99,
        'quantity' => 5,
        'is_visible' => true,
    ]);

    // 3. Create Order
    $item = OrderItem::create(
        productId: $product->id,
        productName: $product->name,
        sku: $product->sku,
        price: 999.99,
        quantity: 1
    );

    $order = Order::create(
        customerId: $customer->id()->value(),
        paymentMethod: 'credit_card',
        items: [$item],
        taxAmount: 189.99,
        shippingAmount: 20.00,
        currency: 'USD'
    );

    $this->orderRepository->save($order);

    // 4. Find by ID
    $found = $this->orderRepository->findById($order->id());

    expect($found)->not->toBeNull()
        ->and($found->orderNumber()->value())->toBe($order->orderNumber()->value())
        ->and($found->customerId())->toBe($customer->id()->value())
        ->and($found->subtotal()->amount())->toBe(999.99)
        ->and($found->taxAmount()->amount())->toBe(189.99)
        ->and($found->shippingAmount()->amount())->toBe(20.00)
        ->and($found->total()->amount())->toBe(1209.98) // 999.99 + 189.99 + 20 = 1209.98
        ->and($found->items())->toHaveCount(1)
        ->and($found->items()[0]->productName())->toBe('Guitarra Eléctrica PRS');

    // 5. Find by Order Number
    $foundByNum = $this->orderRepository->findByOrderNumber(new OrderNumber($order->orderNumber()->value()));
    expect($foundByNum)->not->toBeNull()
        ->and($foundByNum->id()->value())->toBe($order->id()->value());
});

it('filters orders by multiple criteria and calculates metrics correctly', function () {
    $c1 = Customer::create(name: 'Juan Perez', email: 'juan@test.cl');
    $c2 = Customer::create(name: 'Maria Paz', email: 'maria@test.cl');
    $this->customerRepository->save($c1);
    $this->customerRepository->save($c2);

    $p1 = \Src\Product\Infrastructure\Eloquent\Models\Product::create([
        'id' => (string) Str::uuid(),
        'name' => 'Mouse Gamer',
        'slug' => 'mouse-gamer',
        'sku' => 'MOU-1',
        'price' => 25.0,
        'quantity' => 10,
        'is_visible' => true,
    ]);

    // Order 1 (Juan) - Pending - $50
    $item1 = OrderItem::create($p1->id, 'Mouse Gamer', 'MOU-1', 25.0, 2);
    $o1 = Order::create($c1->id()->value(), 'transfer', [$item1]);
    $this->orderRepository->save($o1);

    // Order 2 (Maria) - Delivered - $100
    $item2 = OrderItem::create($p1->id, 'Mouse Gamer', 'MOU-1', 25.0, 4);
    $o2 = Order::create($c2->id()->value(), 'stripe', [$item2]);
    $o2->confirm();
    $o2->process();
    $o2->markAsShipped('Chilexpress');
    $o2->markAsDelivered();
    $this->orderRepository->save($o2);

    // 1. Filtrar por búsqueda 'Juan'
    $filterJuan = $this->orderRepository->filter(new FilterOrdersCriteria(search: 'Juan'));
    expect($filterJuan->total)->toBe(1)
        ->and($filterJuan->data[0]->customerId())->toBe($c1->id()->value());

    // 2. Filtrar por status 'delivered'
    $filterDelivered = $this->orderRepository->filter(new FilterOrdersCriteria(status: 'delivered'));
    expect($filterDelivered->total)->toBe(1)
        ->and($filterDelivered->data[0]->id()->value())->toBe($o2->id()->value());

    // 3. Consultar métricas agregadas
    $metrics = $this->orderRepository->getMetrics();
    expect($metrics->totalOrders)->toBe(2)
        ->and($metrics->pendingOrders)->toBe(1)
        ->and($metrics->completedOrders)->toBe(1)
        ->and($metrics->totalSalesAmount)->toBe(150.0) // 50 + 100 = 150
        ->and($metrics->averageOrderValue)->toBe(75.0);
});
