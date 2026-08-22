<?php

declare(strict_types=1);

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Src\Tenant\Infrastructure\Eloquent\Models\Tenant as ModelsTenant;
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

    if (! Schema::hasTable('products')) {
        (require base_path('database/migrations/tenant/2025_10_28_143038_create_products.php'))->up();
    }
    if (! Schema::hasTable('product_images')) {
        (require base_path('database/migrations/tenant/2025_10_28_143251_create_product_images.php'))->up();
    }

    $tenantId = 't_'.bin2hex(random_bytes(4));
    $this->tenant = ModelsTenant::create([
        'id' => $tenantId,
        'name' => 'Tienda Media Test',
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

    // Fase 0.3-E: /api-tenant/* dejó de estar abierto (hallazgo A5). Las rutas
    // de backoffice exigen ahora sesión de usuario de la tienda; se autentica
    // aquí para todo el archivo.
    $this->tenantUser = actingAsTenantOwner();
});

afterEach(function () {
    if (tenancy()->initialized) {
        tenancy()->end();
    }
});

it('uploads valid product image, returns 201 and stores file in tenant disk', function () {
    $file = UploadedFile::fake()->image('product_photo.jpg', 800, 600);

    $response = $this->post("http://{$this->domain}/api-tenant/product/media/upload", [
        'file' => $file,
        'alt_text' => 'Foto de prueba producto',
    ]);

    $response->assertStatus(201);
    $response->assertJsonPath('status', 'success');
    $response->assertJsonStructure([
        'status',
        'message',
        'code',
        'data' => [
            'url',
            'image_path',
            'path',
            'filename',
            'alt_text',
        ],
    ]);

    $storedPath = $response->json('data.path');
    Storage::disk('public')->assertExists($storedPath);
});

it('rejects invalid mime type with 422 error', function () {
    $file = UploadedFile::fake()->create('document.pdf', 100, 'application/pdf');

    $response = $this->postJson("http://{$this->domain}/api-tenant/product/media/upload", [
        'file' => $file,
    ]);

    $response->assertStatus(422);
    $response->assertJsonPath('status', 'error');
});

it('deletes product image from storage physically', function () {
    $file = UploadedFile::fake()->image('to_delete.png', 400, 400);

    $uploadResponse = $this->post("http://{$this->domain}/api-tenant/product/media/upload", [
        'file' => $file,
    ]);

    $uploadResponse->assertStatus(201);
    $storedPath = $uploadResponse->json('data.path');
    $imageUrl = $uploadResponse->json('data.url');

    Storage::disk('public')->assertExists($storedPath);

    $deleteResponse = $this->deleteJson("http://{$this->domain}/api-tenant/product/media/delete", [
        'image_path' => $imageUrl,
    ]);

    $deleteResponse->assertStatus(200);
    $deleteResponse->assertJsonPath('status', 'success');
    Storage::disk('public')->assertMissing($storedPath);
});
