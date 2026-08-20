<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Schema;
use Inertia\Testing\AssertableInertia as Assert;
use Src\Tenant\Infrastructure\Eloquent\Models\Tenant;
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

    if (! Schema::hasTable('tenants')) {
        Schema::create('tenants', function ($table) {
            $table->uuid('id')->primary();
            $table->string('name')->nullable();
            $table->string('slug')->nullable();
            $table->string('status')->nullable();
            $table->string('request')->nullable();
            $table->json('data')->nullable();
            $table->softDeletes();
            $table->timestamps();
        });
    }

    if (! Tenant::where('id', 'tienda-demo-b2b')->exists()) {
        Tenant::create([
            'id' => 'tienda-demo-b2b',
            'name' => 'Tienda Demo B2B',
            'slug' => 'tienda-demo-b2b',
            'status' => 'active',
            'request' => 'approved',
        ]);
    }
});

test('GET /vender renders merchant landing page with featured stores and plans', function () {
    $response = $this->get('/vender');

    $response->assertStatus(200);
    $response->assertInertia(fn (Assert $page) => $page
        ->component('marketplace/landing/MerchantLandingPage')
        ->has('featured_stores')
        ->has('plans')
        ->has('total_stores_count')
    );
});

test('GET /vende-con-nosotros aliases to merchant landing page', function () {
    $response = $this->get('/vende-con-nosotros');

    $response->assertStatus(200);
    $response->assertInertia(fn (Assert $page) => $page
        ->component('marketplace/landing/MerchantLandingPage')
    );
});
