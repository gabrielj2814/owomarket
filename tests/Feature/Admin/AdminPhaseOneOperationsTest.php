<?php

declare(strict_types=1);

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Src\Monetization\Infrastructure\Eloquent\Models\CommissionSettlement;
use Src\Monetization\Infrastructure\Eloquent\Models\PlatformCommission;
use Src\SupportTicket\Infrastructure\Eloquent\Models\SupportTicket;
use Src\Tenant\Infrastructure\Eloquent\Models\Tenant;
use Src\Tenant\Infrastructure\Eloquent\Models\User;

uses(RefreshDatabase::class);

beforeEach(function () {
    config(['tenancy.central_domains' => ['owomarket.local', 'localhost', '127.0.0.1']]);
});

test('Super Admin can view executive dashboard with aggregated metrics', function () {
    $admin = User::create([
        'id' => (string) Str::uuid(),
        'name' => 'Super Admin Tester',
        'email' => 'admin_'.bin2hex(random_bytes(3)).'@owomarket.local',
        'password' => bcrypt('Password123!'),
        'type' => 'super_admin',
        'is_active' => true,
    ]);

    $response = $this->actingAs($admin)->get("/admin/backoffice/{$admin->id}/dashboard");

    $response->assertStatus(200);
});

test('Super Admin can list, approve and reject payout requests', function () {
    $admin = User::create([
        'id' => (string) Str::uuid(),
        'name' => 'Super Admin Finance',
        'email' => 'finance_'.bin2hex(random_bytes(3)).'@owomarket.local',
        'password' => bcrypt('Password123!'),
        'type' => 'super_admin',
        'is_active' => true,
    ]);

    $tenant = Tenant::create([
        'id' => (string) Str::uuid(),
        'name' => 'Store Payout Test',
        'slug' => 'store-payout-test',
        'status' => 'active',
        'request' => 'approved',
    ]);

    /*
     * Hallazgo T1: este fixture creaba retiros SIN ninguna comision de respaldo — un pago
     * sin ventas detras—, y el test afirmaba que se podian aprobar. Consagraba justo lo que
     * T1 vino a impedir.
     *
     * Ahora la tienda tiene ventas por 200 sin comision, o sea 200 de saldo liquidable: el
     * primer retiro (150) esta respaldado y se aprueba; el segundo se rechaza por decision
     * del administrador, que es lo que el test comprueba de verdad.
     */
    PlatformCommission::create([
        'id' => (string) Str::uuid(),
        'tenant_id' => $tenant->id,
        'order_id' => (string) Str::uuid(),
        // Fase 2: sin `central_order_id` esto es una venta del escaparate, y el comerciante
        // ya cobro directo en su banco: la plataforma no le debe nada y no respalda ningun
        // retiro. Este fixture representa lo contrario --una venta central cobrada por la
        // plataforma--, asi que ahora lo dice.
        'central_order_id' => (string) Str::uuid(),
        'order_number' => 'ORD-PAYOUT-BACKING',
        'order_total' => 200.00,
        'commission_rate' => 0.00,
        'commission_amount' => 0.00,
        'currency' => 'USD',
        'exchange_rate' => 50.00,
        'status' => 'pending',
        // Entregada hace tiempo: retirable. `now()` la dejaria dentro del plazo de
        // garantia del comprador y el saldo la retendria.
        'released_at' => now()->subDays(30),
    ]);

    $payout1 = CommissionSettlement::create([
        'id' => (string) Str::uuid(),
        'settlement_number' => 'PAY-20260820-001',
        'tenant_id' => $tenant->id,
        'type' => 'payout',
        'gross_sales_amount' => 150.00,
        'commission_amount' => 0.00,
        'net_amount' => 150.00,
        'currency' => 'USD',
        'status' => 'pending',
        'payment_method' => 'pago_movil',
        'metadata' => [
            'payment_details' => [
                'bank_name' => 'Banesco',
                'id_number' => 'V-12345678',
                'phone' => '04141234567',
            ],
        ],
    ]);

    $payout2 = CommissionSettlement::create([
        'id' => (string) Str::uuid(),
        'settlement_number' => 'PAY-20260820-002',
        'tenant_id' => $tenant->id,
        'type' => 'payout',
        'gross_sales_amount' => 50.00,
        'commission_amount' => 0.00,
        'net_amount' => 50.00,
        'currency' => 'USD',
        'status' => 'pending',
        'payment_method' => 'binance_pay',
        'metadata' => [
            'payment_details' => [
                'pay_id' => '987654321',
            ],
        ],
    ]);

    // 1. List payouts page and API
    $pageResponse = $this->actingAs($admin)->get("/admin/backoffice/{$admin->id}/payouts");
    $pageResponse->assertStatus(200);

    $apiListResponse = $this->actingAs($admin)->getJson('/admin/api/payouts?status=pending');
    $apiListResponse->assertStatus(200)
        ->assertJsonPath('status', 'success')
        ->assertJsonPath('data.metrics.pending_count', 2);

    // 2. Approve Payout 1
    $approveResponse = $this->actingAs($admin)->postJson("/admin/api/payouts/{$payout1->id}/approve", [
        'payment_reference' => '00482910482',
        'notes' => 'Pago Móvil liquidado vía Banesco',
    ]);

    $approveResponse->assertStatus(200)
        ->assertJsonPath('status', 'success')
        ->assertJsonPath('data.payment_reference', '00482910482');

    $this->assertDatabaseHas('commission_settlements', [
        'id' => $payout1->id,
        'status' => 'settled',
        'payment_reference' => '00482910482',
    ]);

    // 3. Reject Payout 2
    $rejectResponse = $this->actingAs($admin)->postJson("/admin/api/payouts/{$payout2->id}/reject", [
        'rejection_reason' => 'Binance Pay ID no coincide con el titular',
    ]);

    $rejectResponse->assertStatus(200)
        ->assertJsonPath('status', 'success');

    $this->assertDatabaseHas('commission_settlements', [
        'id' => $payout2->id,
        'status' => 'cancelled',
        'notes' => 'Binance Pay ID no coincide con el titular',
    ]);
});

test('Super Admin can manage and reply to support tickets in central helpdesk', function () {
    $admin = User::create([
        'id' => (string) Str::uuid(),
        'name' => 'Super Admin Support',
        'email' => 'support_'.bin2hex(random_bytes(3)).'@owomarket.local',
        'password' => bcrypt('Password123!'),
        'type' => 'super_admin',
        'is_active' => true,
    ]);

    $ticket = SupportTicket::create([
        'id' => (string) Str::uuid(),
        'ticket_number' => 'TCK-20260820-001',
        'requester_type' => 'tenant_owner',
        'user_id' => $admin->id,
        'tenant_id' => 'chivostore',
        'category' => 'technical',
        'priority' => 'high',
        'status' => 'open',
        'subject' => 'Problema con sincronización de catálogo',
        'description' => 'Los productos no se actualizan en el marketplace.',
        'attachments' => [],
        'last_reply_at' => now(),
    ]);

    // 1. View Helpdesk Page
    $pageResponse = $this->actingAs($admin)->get("/admin/backoffice/{$admin->id}/support");
    $pageResponse->assertStatus(200);

    // 2. Filter Tickets API
    $filterResponse = $this->actingAs($admin)->postJson('/admin/api/support/tickets/filter', [
        'status' => 'open',
        'priority' => 'high',
    ]);

    $filterResponse->assertStatus(200)
        ->assertJsonPath('status', 'success')
        ->assertJsonPath('data.metrics.total_open', 1);

    // 3. Reply to Ticket as Admin
    $replyResponse = $this->actingAs($admin)->postJson("/admin/api/support/tickets/{$ticket->id}/reply", [
        'message' => 'Hemos reiniciado el caché del catálogo y ya se visualizan tus productos.',
        'status' => 'resolved',
    ]);

    $replyResponse->assertStatus(200)
        ->assertJsonPath('status', 'success');

    $this->assertDatabaseHas('support_ticket_messages', [
        'ticket_id' => $ticket->id,
        'sender_type' => 'admin',
        'message' => 'Hemos reiniciado el caché del catálogo y ya se visualizan tus productos.',
    ]);

    $this->assertDatabaseHas('support_tickets', [
        'id' => $ticket->id,
        'status' => 'resolved',
    ]);

    // 4. Update status/priority
    $statusResponse = $this->actingAs($admin)->patchJson("/admin/api/support/tickets/{$ticket->id}/status", [
        'status' => 'closed',
        'priority' => 'medium',
    ]);

    $statusResponse->assertStatus(200)
        ->assertJsonPath('status', 'success');

    $this->assertDatabaseHas('support_tickets', [
        'id' => $ticket->id,
        'status' => 'closed',
        'priority' => 'medium',
    ]);
});
