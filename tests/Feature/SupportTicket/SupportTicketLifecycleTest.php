<?php

declare(strict_types=1);

use Src\CentralCustomer\Infrastructure\Eloquent\Models\CentralCustomer;
use Src\SupportTicket\Infrastructure\Eloquent\Models\SupportTicket;
use Src\SupportTicket\Infrastructure\Eloquent\Models\SupportTicketMessage;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Src\Tenant\Infrastructure\Eloquent\Models\Domain;
use Src\Tenant\Infrastructure\Eloquent\Models\Tenant;
use Src\Tenant\Infrastructure\Eloquent\Models\User;
use Stancl\Tenancy\Bootstrappers\DatabaseTenancyBootstrapper;
use Stancl\Tenancy\Events\TenantCreated;
use Stancl\Tenancy\Events\TenantDeleted;

beforeEach(function () {
    Event::fake([TenantCreated::class, TenantDeleted::class]);
    Storage::fake('public');

    config([
        'tenancy.bootstrappers' => array_values(array_filter(
            config('tenancy.bootstrappers', []),
            fn ($bootstrapper) => $bootstrapper !== DatabaseTenancyBootstrapper::class
        )),
    ]);

    if (! Schema::hasTable('support_tickets')) {
        (require base_path('database/migrations/2026_08_19_000013_create_support_tickets_tables.php'))->up();
    }
    if (! Schema::hasTable('central_customers')) {
        (require base_path('database/migrations/2026_08_19_000001_create_central_customers_tables.php'))->up();
    }
});

test('Tenant Owner can create support ticket with multiple photos and videos', function () {
    $user = User::create([
        'id' => (string) Str::uuid(),
        'name' => 'Owner Ticket Tester',
        'email' => 'owner_ticket_'.bin2hex(random_bytes(3)).'@example.com',
        'password' => bcrypt('Password123!'),
        'type' => 'tenant_owner',
    ]);

    $image = UploadedFile::fake()->image('error_screenshot.png', 800, 600);
    $video = UploadedFile::fake()->create('error_demo.mp4', 2048, 'video/mp4');

    $response = $this->actingAs($user)->post('/tenant/api/support/tickets', [
        'user_id' => $user->id,
        'requester_type' => 'tenant_owner',
        'subject' => 'Error al procesar pago móvil en checkout',
        'description' => 'Los clientes reportan que no se valida la referencia de Pago Móvil en el paso 3.',
        'category' => 'technical_error',
        'priority' => 'high',
        'files' => [$image, $video],
    ]);

    $response->assertStatus(201)
        ->assertJsonPath('status', 'success')
        ->assertJsonPath('data.subject', 'Error al procesar pago móvil en checkout')
        ->assertJsonPath('data.priority', 'high');

    $ticketId = $response->json('data.id');
    $ticket = SupportTicket::with('messages')->find($ticketId);

    expect($ticket)->not->toBeNull();
    expect($ticket->attachments)->toHaveCount(2);
    expect($ticket->attachments[0]['type'])->toBe('image');
    expect($ticket->attachments[1]['type'])->toBe('video');
    expect($ticket->messages)->toHaveCount(1);
});

test('Customer can create support ticket, list tickets, and reply with attachments', function () {
    $customer = CentralCustomer::create([
        'id' => (string) Str::uuid(),
        'name' => 'Ana Gómez',
        'email' => 'ana_ticket_'.bin2hex(random_bytes(3)).'@example.com',
        'phone' => '04141234567',
        'password' => bcrypt('Password123!'),
        'is_active' => true,
    ]);

    $image = UploadedFile::fake()->image('product_defect.jpg', 600, 600);

    // 1. Create Ticket
    $createResponse = $this->withSession(['central_customer_id' => $customer->id])->post('/api/support/tickets', [
        'user_id' => $customer->id,
        'requester_type' => 'customer',
        'subject' => 'Producto recibido con detalle',
        'description' => 'El teclado llegó con una tecla suelta.',
        'category' => 'order_issue',
        'files' => [$image],
    ]);

    $createResponse->assertStatus(201);
    $ticketId = $createResponse->json('data.id');

    // 2. List user tickets
    $listResponse = $this->withSession(['central_customer_id' => $customer->id])->getJson("/api/support/tickets?user_id={$customer->id}");
    $listResponse->assertStatus(200)
        ->assertJsonPath('status', 'success')
        ->assertJsonPath('data.counts.total', 1);

    // 3. Add message reply with video
    $video = UploadedFile::fake()->create('unboxing_proof.mp4', 3000, 'video/mp4');
    $replyResponse = $this->withSession(['central_customer_id' => $customer->id])->post("/api/support/tickets/{$ticketId}/messages", [
        'user_id' => $customer->id,
        'sender_type' => 'customer',
        'message' => 'Adjunto video del desempaque para corroborar el estado.',
        'files' => [$video],
    ]);

    $replyResponse->assertStatus(201)
        ->assertJsonPath('status', 'success')
        ->assertJsonPath('data.attachments.0.type', 'video');

    // 4. Update ticket status to resolved
    $statusResponse = $this->withSession(['central_customer_id' => $customer->id])->patchJson("/api/support/tickets/{$ticketId}/status", [
        'status' => 'resolved',
    ]);

    $statusResponse->assertStatus(200)
        ->assertJsonPath('status', 'success')
        ->assertJsonPath('data.status', 'resolved');

    expect(SupportTicket::find($ticketId)->status)->toBe('resolved');
});

test('Support ticket web views render successfully', function () {
    $user = User::create([
        'id' => (string) Str::uuid(),
        'name' => 'Owner Views Tester',
        'email' => 'owner_view_'.bin2hex(random_bytes(3)).'@example.com',
        'password' => bcrypt('Password123!'),
        'type' => 'tenant_owner',
    ]);

    $customer = CentralCustomer::create([
        'id' => (string) Str::uuid(),
        'name' => 'Carlos Pérez',
        'email' => 'carlos_view_'.bin2hex(random_bytes(3)).'@example.com',
        'phone' => '04129876543',
        'password' => bcrypt('Password123!'),
        'is_active' => true,
    ]);

    $this->actingAs($user)->get("/tenant/owner/backoffice/{$user->id}/support")->assertStatus(200);
    $this->withSession(['central_customer_id' => $customer->id])->get("/account/support")->assertStatus(200);
});

test('Anonymous requests to the central support API are rejected', function () {
    $response = $this->getJson('/api/support/tickets');

    $response->assertStatus(401);
});

test('A customer cannot read or reply to another customer ticket', function () {
    $victim = CentralCustomer::create([
        'id' => (string) Str::uuid(),
        'name' => 'Víctima',
        'email' => 'victim_'.bin2hex(random_bytes(3)).'@example.com',
        'phone' => '04141111111',
        'password' => bcrypt('Password123!'),
        'is_active' => true,
    ]);

    $attacker = CentralCustomer::create([
        'id' => (string) Str::uuid(),
        'name' => 'Atacante',
        'email' => 'attacker_'.bin2hex(random_bytes(3)).'@example.com',
        'phone' => '04142222222',
        'password' => bcrypt('Password123!'),
        'is_active' => true,
    ]);

    $createResponse = $this->withSession(['central_customer_id' => $victim->id])->post('/api/support/tickets', [
        'subject' => 'Ticket privado de la víctima',
        'description' => 'Contiene datos sensibles.',
    ]);
    $createResponse->assertStatus(201);
    $ticketId = $createResponse->json('data.id');

    // Pasar user_id ajeno en la query ya no cambia nada: la identidad sale de la sesión.
    $this->withSession(['central_customer_id' => $attacker->id])
        ->getJson("/api/support/tickets/{$ticketId}?user_id={$victim->id}")
        ->assertStatus(404);

    $this->withSession(['central_customer_id' => $attacker->id])
        ->post("/api/support/tickets/{$ticketId}/messages", [
            'user_id' => $victim->id,
            'sender_type' => 'admin',
            'sender_name' => 'Soporte OwoMarket',
            'message' => 'Confirme su contraseña',
        ])
        ->assertStatus(403);

    $this->withSession(['central_customer_id' => $attacker->id])
        ->patchJson("/api/support/tickets/{$ticketId}/status", ['status' => 'closed'])
        ->assertStatus(403);

    expect(SupportTicket::find($ticketId)->status)->toBe('open');
    expect(SupportTicketMessage::where('ticket_id', $ticketId)->count())->toBe(1); // sólo el mensaje inicial
});

test('A customer message cannot spoof sender_type as admin', function () {
    $customer = CentralCustomer::create([
        'id' => (string) Str::uuid(),
        'name' => 'Cliente Normal',
        'email' => 'normal_'.bin2hex(random_bytes(3)).'@example.com',
        'phone' => '04143333333',
        'password' => bcrypt('Password123!'),
        'is_active' => true,
    ]);

    $createResponse = $this->withSession(['central_customer_id' => $customer->id])->post('/api/support/tickets', [
        'subject' => 'Consulta',
        'description' => 'Necesito ayuda con mi pedido.',
    ]);
    $ticketId = $createResponse->json('data.id');

    $replyResponse = $this->withSession(['central_customer_id' => $customer->id])
        ->post("/api/support/tickets/{$ticketId}/messages", [
            'sender_type' => 'admin',
            'sender_name' => 'Soporte OwoMarket',
            'message' => 'Este mensaje no debería verse como oficial.',
        ]);

    $replyResponse->assertStatus(201)
        ->assertJsonPath('data.sender_type', 'customer')
        ->assertJsonPath('data.sender_id', $customer->id);
});
