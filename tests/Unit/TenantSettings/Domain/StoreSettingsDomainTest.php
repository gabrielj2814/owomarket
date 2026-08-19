<?php

declare(strict_types=1);

use Src\TenantSettings\Domain\Entities\StoreSettings;

it('creates StoreSettings from key value map with defaults', function () {
    $settings = StoreSettings::fromKeyValueMap([
        'store_name' => 'OwO Store Chile',
        'currency' => 'CLP',
        'social_instagram' => 'https://instagram.com/owostore',
    ]);

    expect($settings->storeName())->toBe('OwO Store Chile')
        ->and($settings->currency())->toBe('CLP')
        ->and($settings->storeEmail())->toBe('contacto@tienda.com')
        ->and($settings->socialInstagram())->toBe('https://instagram.com/owostore')
        ->and($settings->logoUrl())->toBeNull();

    $arr = $settings->toArray();
    expect($arr)->toHaveKeys(['general', 'appearance', 'social', 'seo'])
        ->and($arr['general']['store_name'])->toBe('OwO Store Chile')
        ->and($arr['general']['currency'])->toBe('CLP')
        ->and($arr['social']['instagram'])->toBe('https://instagram.com/owostore');
});

it('converts StoreSettings to key value map cleanly', function () {
    $settings = new StoreSettings(
        storeName: 'Tech Store',
        storeEmail: 'admin@tech.com',
        currency: 'USD',
        contactPhone: '+56912345678',
        seoTitle: 'Tech Store - Los mejores laptops'
    );

    $map = $settings->toKeyValueMap();
    expect($map['store_name'])->toBe('Tech Store')
        ->and($map['store_email'])->toBe('admin@tech.com')
        ->and($map['contact_phone'])->toBe('+56912345678')
        ->and($map['seo_title'])->toBe('Tech Store - Los mejores laptops');
});
