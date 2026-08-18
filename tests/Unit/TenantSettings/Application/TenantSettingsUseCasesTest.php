<?php

declare(strict_types=1);

use Src\TenantSettings\Application\DTOs\SaveSettingData;
use Src\TenantSettings\Application\DTOs\UpdateStoreSettingsData;
use Src\TenantSettings\Application\Repositories\TenantSettingsRepositoryInterface;
use Src\TenantSettings\Application\UseCases\DeleteSettingUseCase;
use Src\TenantSettings\Application\UseCases\GetSettingByKeyUseCase;
use Src\TenantSettings\Application\UseCases\GetStoreSettingsUseCase;
use Src\TenantSettings\Application\UseCases\ListAllSettingsUseCase;
use Src\TenantSettings\Application\UseCases\ListSettingsByGroupUseCase;
use Src\TenantSettings\Application\UseCases\SaveSettingUseCase;
use Src\TenantSettings\Application\UseCases\UpdateStoreSettingsUseCase;
use Src\TenantSettings\Domain\Entities\StoreSettings;
use Src\TenantSettings\Domain\Entities\TenantSetting;
use Src\TenantSettings\Domain\Exceptions\SettingNotFoundException;
use Src\TenantSettings\Domain\ValueObjects\SettingGroup;
use Src\TenantSettings\Domain\ValueObjects\SettingKey;
use Src\TenantSettings\Domain\ValueObjects\SettingType;

beforeEach(function () {
    $this->repository = Mockery::mock(TenantSettingsRepositoryInterface::class);
});

afterEach(function () {
    Mockery::close();
});

it('GetStoreSettingsUseCase returns current StoreSettings', function () {
    $expected = new StoreSettings(
        storeName: 'Mi Tienda Electrónica',
        currency: 'USD'
    );

    $this->repository->shouldReceive('getStoreSettings')
        ->once()
        ->andReturn($expected);

    $useCase = new GetStoreSettingsUseCase($this->repository);
    $result = $useCase->execute();

    expect($result->storeName())->toBe('Mi Tienda Electrónica')
        ->and($result->currency())->toBe('USD');
});

it('UpdateStoreSettingsUseCase updates and saves modified StoreSettings', function () {
    $current = new StoreSettings(
        storeName: 'Antiguo Nombre',
        storeEmail: 'old@email.com',
        currency: 'USD'
    );

    $this->repository->shouldReceive('getStoreSettings')
        ->once()
        ->andReturn($current);

    $this->repository->shouldReceive('updateStoreSettings')
        ->once()
        ->with(Mockery::on(function (StoreSettings $s) {
            return $s->storeName() === 'Nuevo Nombre' && $s->currency() === 'CLP';
        }));

    $dto = new UpdateStoreSettingsData(
        storeName: 'Nuevo Nombre',
        currency: 'CLP'
    );

    $useCase = new UpdateStoreSettingsUseCase($this->repository);
    $result = $useCase->execute($dto);

    expect($result->storeName())->toBe('Nuevo Nombre')
        ->and($result->currency())->toBe('CLP')
        ->and($result->storeEmail())->toBe('old@email.com');
});

it('GetSettingByKeyUseCase returns setting or throws exception', function () {
    $setting = TenantSetting::create(
        key: new SettingKey('store_theme'),
        value: 'dark',
        type: SettingType::string(),
        group: SettingGroup::appearance()
    );

    $this->repository->shouldReceive('findByKey')
        ->once()
        ->with(Mockery::on(fn (SettingKey $k) => $k->value() === 'store_theme'))
        ->andReturn($setting);

    $useCase = new GetSettingByKeyUseCase($this->repository);
    $result = $useCase->execute('store_theme');

    expect($result->value())->toBe('dark');
});

it('GetSettingByKeyUseCase throws SettingNotFoundException when key not found', function () {
    $this->repository->shouldReceive('findByKey')
        ->once()
        ->andReturnNull();

    $useCase = new GetSettingByKeyUseCase($this->repository);
    $useCase->execute('non_existent_key');
})->throws(SettingNotFoundException::class);

it('SaveSettingUseCase creates new setting or updates existing', function () {
    $this->repository->shouldReceive('findByKey')
        ->once()
        ->andReturnNull();

    $this->repository->shouldReceive('save')
        ->once()
        ->with(Mockery::type(TenantSetting::class));

    $useCase = new SaveSettingUseCase($this->repository);
    $dto = new SaveSettingData(
        key: 'enable_whatsapp_chat',
        value: '1',
        type: 'boolean',
        group: 'social'
    );

    $result = $useCase->execute($dto);
    expect($result->key()->value())->toBe('enable_whatsapp_chat')
        ->and($result->typedValue())->toBeTrue()
        ->and($result->group()->value())->toBe('social');
});

it('ListSettingsByGroupUseCase returns settings filtered by group', function () {
    $setting = TenantSetting::create(
        key: new SettingKey('logo_url'),
        value: 'https://cdn.com/logo.png',
        type: SettingType::string(),
        group: SettingGroup::appearance()
    );

    $this->repository->shouldReceive('listByGroup')
        ->once()
        ->with(Mockery::on(fn (SettingGroup $g) => $g->value() === 'appearance'))
        ->andReturn([$setting]);

    $useCase = new ListSettingsByGroupUseCase($this->repository);
    $results = $useCase->execute('appearance');

    expect($results)->toHaveCount(1)
        ->and($results[0]->key()->value())->toBe('logo_url');
});

it('ListAllSettingsUseCase returns all store settings', function () {
    $this->repository->shouldReceive('listAll')
        ->once()
        ->andReturn([]);

    $useCase = new ListAllSettingsUseCase($this->repository);
    $results = $useCase->execute();

    expect($results)->toBeArray()->toBeEmpty();
});

it('DeleteSettingUseCase delegates to repository delete', function () {
    $this->repository->shouldReceive('delete')
        ->once()
        ->with(Mockery::on(fn (SettingKey $k) => $k->value() === 'cache_enabled'));

    $useCase = new DeleteSettingUseCase($this->repository);
    $useCase->execute('cache_enabled');
    expect(true)->toBeTrue();
});
