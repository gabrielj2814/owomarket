<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Src\Tenant\Infrastructure\Eloquent\Models\Tenant as ModelsTenant;
use Src\TenantSettings\Application\Repositories\TenantSettingsRepositoryInterface;
use Src\TenantSettings\Domain\Entities\StoreSettings;
use Src\TenantSettings\Domain\Entities\TenantSetting;
use Src\TenantSettings\Domain\ValueObjects\SettingGroup;
use Src\TenantSettings\Domain\ValueObjects\SettingKey;
use Src\TenantSettings\Domain\ValueObjects\SettingType;
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

    if (! Schema::hasTable('tenant_settings')) {
        (require base_path('database/migrations/tenant/2025_10_28_144914_create_tenant_settings.php'))->up();
    }

    $tenantId = 't_'.bin2hex(random_bytes(4));
    $this->tenant = ModelsTenant::create([
        'id' => $tenantId,
        'name' => 'Settings Test Store',
        'slug' => $tenantId,
        'status' => 'active',
        'request' => 'approved',
    ]);
    $domain = "{$tenantId}.localhost";
    $this->tenant->domains()->create([
        'id' => (string) Str::uuid(),
        'domain' => $domain,
    ]);

    tenancy()->initialize($this->tenant);
    $this->repository = app(TenantSettingsRepositoryInterface::class);
});

afterEach(function () {
    if (tenancy()->initialized) {
        tenancy()->end();
    }
});

it('saves, retrieves, updates and deletes TenantSetting in tenant database', function () {
    $setting = TenantSetting::create(
        key: new SettingKey('primary_color'),
        value: '#3B82F6',
        type: SettingType::string(),
        group: SettingGroup::appearance()
    );

    $this->repository->save($setting);

    $found = $this->repository->findByKey(new SettingKey('primary_color'));
    expect($found)->not->toBeNull()
        ->and($found->value())->toBe('#3B82F6')
        ->and($found->group()->value())->toBe('appearance');

    $foundById = $this->repository->findById($setting->id());
    expect($foundById)->not->toBeNull()
        ->and($foundById->key()->value())->toBe('primary_color');

    // List by group
    $appearanceList = $this->repository->listByGroup(SettingGroup::appearance());
    expect($appearanceList)->toHaveCount(1);

    // Delete
    $this->repository->delete(new SettingKey('primary_color'));
    $afterDelete = $this->repository->findByKey(new SettingKey('primary_color'));
    expect($afterDelete)->toBeNull();
});

it('retrieves default StoreSettings and updates all settings across groups in tenant database', function () {
    // 1. Initial State: Returns default StoreSettings
    $initial = $this->repository->getStoreSettings();
    expect($initial->storeName())->toBe('Mi Tienda Online')
        ->and($initial->currency())->toBe('USD')
        ->and($initial->logoUrl())->toBeNull();

    // 2. Update StoreSettings
    $updated = new StoreSettings(
        storeName: 'OwOMarket Tienda Oficial',
        storeEmail: 'admin@owomarket.com',
        currency: 'CLP',
        contactPhone: '+56987654321',
        address: 'Av. Providencia 1234, Santiago',
        logoUrl: 'https://cdn.owomarket.com/logo.png',
        bannerUrl: 'https://cdn.owomarket.com/banner.png',
        socialFacebook: 'https://facebook.com/owomarket',
        socialInstagram: 'https://instagram.com/owomarket',
        socialWhatsapp: '+56987654321',
        seoTitle: 'OwOMarket - Todo en Tecnología',
        seoDescription: 'La tienda líder de comercio electrónico en Chile',
        seoKeywords: 'ecommerce, tecnologia, laptops, celulares'
    );

    $this->repository->updateStoreSettings($updated);

    // 3. Retrieve updated StoreSettings from DB
    $persisted = $this->repository->getStoreSettings();
    expect($persisted->storeName())->toBe('OwOMarket Tienda Oficial')
        ->and($persisted->storeEmail())->toBe('admin@owomarket.com')
        ->and($persisted->currency())->toBe('CLP')
        ->and($persisted->contactPhone())->toBe('+56987654321')
        ->and($persisted->address())->toBe('Av. Providencia 1234, Santiago')
        ->and($persisted->logoUrl())->toBe('https://cdn.owomarket.com/logo.png')
        ->and($persisted->socialFacebook())->toBe('https://facebook.com/owomarket')
        ->and($persisted->seoTitle())->toBe('OwOMarket - Todo en Tecnología');

    // 4. Verify group grouping in database
    $generalGroup = $this->repository->listByGroup(SettingGroup::general());
    expect($generalGroup)->toHaveCount(5); // store_name, store_email, currency, contact_phone, address

    $appearanceGroup = $this->repository->listByGroup(SettingGroup::appearance());
    expect($appearanceGroup)->toHaveCount(2); // logo_url, banner_url
});
