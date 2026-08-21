<?php

declare(strict_types=1);

namespace Tests\Feature\Admin;

use Src\CentralCustomer\Infrastructure\Eloquent\Models\CentralCustomer;
use Src\Order\Infrastructure\Eloquent\Models\CentralOrder;
use Src\Order\Infrastructure\Eloquent\Models\CentralOrderItem;
use Src\Product\Infrastructure\Eloquent\Models\CentralProduct;
use Src\Monetization\Infrastructure\Eloquent\Models\CommissionSettlement;
use Src\Monetization\Infrastructure\Eloquent\Models\PlatformCommission;
use Src\Tenant\Infrastructure\Eloquent\Models\TenantOwnerSsoToken;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Str;
use Src\Tenant\Infrastructure\Eloquent\Models\Tenant;
use Src\Tenant\Infrastructure\Eloquent\Models\User;
use Stancl\Tenancy\Bootstrappers\DatabaseTenancyBootstrapper;
use Stancl\Tenancy\Events\TenantCreated;
use Stancl\Tenancy\Events\TenantDeleted;
use Tests\TestCase;

final class AdminPhaseTwoOperationsTest extends TestCase
{
    use RefreshDatabase;

    private User $adminUser;
    private Tenant $tenant;

    protected function setUp(): void
    {
        parent::setUp();

        Event::fake([TenantCreated::class, TenantDeleted::class]);

        config([
            'tenancy.bootstrappers' => array_values(array_filter(
                config('tenancy.bootstrappers', []),
                fn ($bootstrapper) => $bootstrapper !== DatabaseTenancyBootstrapper::class
            )),
        ]);

        $this->adminUser = User::create([
            'id' => (string) Str::uuid(),
            'name' => 'Super Admin Test',
            'email' => 'admin_' . Str::random(6) . '@owomarket.com',
            'password' => bcrypt('password123'),
            'type' => 'super_admin',
            'is_active' => true,
        ]);

        $this->tenant = Tenant::create([
            'id' => 'shop-' . Str::random(6),
            'name' => 'Tienda Tech Demo',
            'slug' => 'tech-demo-' . Str::random(4),
            'status' => 'active',
            'request' => 'approved',
        ]);

        $this->tenant->domains()->create([
            'id' => (string) Str::uuid(),
            'domain' => "{$this->tenant->slug}.owomarket.local",
        ]);
    }

    public function test_super_admin_can_view_tenant_360_detail_generate_sso_and_update_governance(): void
    {
        $this->actingAs($this->adminUser);

        // 1. Consultar vista Inertia 360°
        $responseView = $this->get("/tenant/backoffice/{$this->adminUser->id}/module/tenant/{$this->tenant->id}/360");
        $responseView->assertStatus(200);

        // 2. Consultar API 360 data
        $responseApi = $this->getJson("/admin/api/tenants/{$this->tenant->id}/360-data");
        $responseApi->assertStatus(200)
            ->assertJson([
                'status' => 'success',
                'code' => 200,
            ])
            ->assertJsonPath('data.tenant.id', $this->tenant->id);

        // 3. Generar token SSO 1-Click
        $responseSso = $this->postJson("/admin/api/tenants/{$this->tenant->id}/sso-token");
        $responseSso->assertStatus(200)
            ->assertJson([
                'status' => 'success',
                'code' => 200,
            ]);

        $this->assertNotEmpty($responseSso->json('data.token'));
        $this->assertStringContainsString('/auth/sso?token=', $responseSso->json('data.sso_url'));

        // 4. Actualizar gobernanza (suspender tienda con motivo)
        $responseGov = $this->patchJson("/admin/api/tenants/{$this->tenant->id}/governance-status", [
            'status' => 'suspended',
            'reason' => 'Verificación de documentos pendiente',
            'admin_notes' => 'Tienda contactada por WhatsApp.',
        ]);

        $responseGov->assertStatus(200);
        $this->tenant->refresh();
        $this->assertSame('suspended', $this->tenant->status);
        $this->assertSame('Tienda contactada por WhatsApp.', $this->tenant->admin_notes);
    }

    public function test_super_admin_can_manage_central_customers_directory_and_toggle_status(): void
    {
        $this->actingAs($this->adminUser);

        $customer = CentralCustomer::create([
            'id' => (string) Str::uuid(),
            'name' => 'Carlos Comprador',
            'email' => 'carlos_' . Str::random(5) . '@example.com',
            'password' => bcrypt('secret123'),
            'is_active' => true,
        ]);

        // 1. Listar clientes
        $responseList = $this->getJson('/admin/api/customers');
        $responseList->assertStatus(200)
            ->assertJson([
                'status' => 'success',
                'code' => 200,
            ]);
        $this->assertGreaterThanOrEqual(1, $responseList->json('data.metrics.total_customers'));

        // 2. Consultar expediente 360 del cliente
        $responseDetail = $this->getJson("/admin/api/customers/{$customer->id}/detail");
        $responseDetail->assertStatus(200)
            ->assertJsonPath('data.customer.id', $customer->id);

        // 3. Bloquear cliente
        $responseToggle = $this->patchJson("/admin/api/customers/{$customer->id}/toggle-status", [
            'reason' => 'Sospecha de contracargo bancario',
        ]);
        $responseToggle->assertStatus(200);

        $customer->refresh();
        $this->assertFalse($customer->is_active);
        $this->assertStringContainsString('Sospecha de contracargo bancario', $customer->notes);
    }

    public function test_super_admin_can_monitor_global_orders_and_resolve_dispute(): void
    {
        $this->actingAs($this->adminUser);

        $order = CentralOrder::create([
            'id' => (string) Str::uuid(),
            'order_number' => 'ORD-' . strtoupper(Str::random(8)),
            'customer_name' => 'Ana Compradora',
            'customer_email' => 'ana@example.com',
            'subtotal' => 100.00,
            'total' => 120.50,
            'payment_method' => 'pago_movil',
            'payment_status' => 'paid',
            'payment_details' => ['reference' => 'PM-9928172'],
            'status' => 'processing',
            'metadata' => ['tenant_id' => $this->tenant->id],
        ]);

        // 1. Listar órdenes globales
        $responseOrders = $this->getJson('/admin/api/orders');
        $responseOrders->assertStatus(200)
            ->assertJson([
                'status' => 'success',
                'code' => 200,
            ]);
        $this->assertGreaterThanOrEqual(1, $responseOrders->json('data.metrics.total_orders'));

        // 2. Consultar detalle de orden
        $responseDetail = $this->getJson("/admin/api/orders/{$order->id}/detail");
        $responseDetail->assertStatus(200)
            ->assertJsonPath('data.order.id', $order->id);

        // 3. Resolver disputa marcando como reembolsada
        $responseDispute = $this->postJson("/admin/api/orders/{$order->id}/resolve-dispute", [
            'resolution_type' => 'refund',
            'reason' => 'Comprobante de devolución bancaria emitido',
            'notes' => 'Acuerdo directo con el cliente satisfecho.',
        ]);

        $responseDispute->assertStatus(200);
        $order->refresh();
        $this->assertSame('refunded', $order->status);
        $this->assertSame('refunded', $order->payment_status);
        $this->assertSame('refund', $order->metadata['dispute_resolution']['resolution_type']);
    }
}
