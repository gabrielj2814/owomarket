<?php

declare(strict_types=1);

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Src\SupportTicket\Infrastructure\Eloquent\Models\SupportTicket;
use Src\Tenant\Infrastructure\Eloquent\Models\Tenant as ModelsTenant;
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

    $tenantId = 't_'.bin2hex(random_bytes(4));
    $this->tenant = ModelsTenant::create([
        'id' => $tenantId,
        'name' => 'Store Local Support Tester',
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
});

afterEach(function () {
    if (tenancy()->initialized) {
        tenancy()->end();
    }
});

test('Store Admin can render support page inside local tenant backoffice', function () {
    $user = User::create([
        'id' => (string) Str::uuid(),
        'name' => 'Local Store Manager',
        'email' => 'local_mgr_'.bin2hex(random_bytes(3)).'@example.com',
        'password' => bcrypt('Password123!'),
        'type' => 'tenant_owner',
    ]);

    $response = $this->actingAs($user)->get("http://{$this->domain}/support/backoffice/{$user->id}/module");
    $response->assertStatus(200);
});

test('Store Admin can create ticket and send replies with multimedia in tenant store context', function () {
    $user = User::create([
        'id' => (string) Str::uuid(),
        'name' => 'Local Store Operator',
        'email' => 'local_op_'.bin2hex(random_bytes(3)).'@example.com',
        'password' => bcrypt('Password123!'),
        'type' => 'tenant_owner',
    ]);

    $image = UploadedFile::fake()->image('billing_issue.png', 800, 600);
    $video = UploadedFile::fake()->create('tax_calculation_error.mp4', 1500, 'video/mp4');

    // 1. Create ticket via tenant API
    $createResponse = $this->actingAs($user)->post("http://{$this->domain}/support/api/tickets", [
        'user_id' => $user->id,
        'subject' => 'Fallo al calcular IVA en facturas fiscales',
        'description' => 'Al emitir una factura en Bs., el monto exento no se discrimina en el PDF.',
        'category' => 'billing_taxes',
        'priority' => 'high',
        'files' => [$image, $video],
    ]);

    $createResponse->assertStatus(201)
        ->assertJsonPath('status', 'success')
        ->assertJsonPath('data.subject', 'Fallo al calcular IVA en facturas fiscales')
        ->assertJsonPath('data.tenant_id', $this->tenant->id);

    $ticketId = $createResponse->json('data.id');
    $ticket = SupportTicket::find($ticketId);

    expect($ticket)->not->toBeNull();
    expect($ticket->attachments)->toHaveCount(2);

    // 2. List tickets in tenant store
    $listResponse = $this->actingAs($user)->getJson("http://{$this->domain}/support/api/tickets?user_id={$user->id}");
    $listResponse->assertStatus(200)
        ->assertJsonPath('status', 'success')
        ->assertJsonPath('data.counts.total', 1);

    // 3. Add reply message
    $replyImage = UploadedFile::fake()->image('clarification.jpg');
    $replyResponse = $this->actingAs($user)->post("http://{$this->domain}/support/api/tickets/{$ticketId}/messages", [
        'user_id' => $user->id,
        'message' => 'Ocurre específicamente con clientes jurídicos con RIF J-',
        'files' => [$replyImage],
    ]);

    $replyResponse->assertStatus(201)
        ->assertJsonPath('status', 'success')
        ->assertJsonPath('data.attachments.0.type', 'image');
});
