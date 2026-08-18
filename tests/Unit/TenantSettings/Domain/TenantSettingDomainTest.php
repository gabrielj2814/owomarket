<?php

declare(strict_types=1);

use Src\TenantSettings\Domain\Entities\TenantSetting;
use Src\TenantSettings\Domain\ValueObjects\SettingGroup;
use Src\TenantSettings\Domain\ValueObjects\SettingKey;
use Src\TenantSettings\Domain\ValueObjects\SettingType;

it('creates TenantSetting and casts typed value correctly', function () {
    $setting = TenantSetting::create(
        key: new SettingKey('enable_store_reviews'),
        value: '1',
        type: SettingType::boolean(),
        group: SettingGroup::general()
    );

    expect($setting->key()->value())->toBe('enable_store_reviews')
        ->and($setting->value())->toBe('1')
        ->and($setting->typedValue())->toBeTrue()
        ->and($setting->type()->value())->toBe('boolean')
        ->and($setting->group()->value())->toBe('general');

    $arr = $setting->toArray();
    expect($arr)->toHaveKeys(['id', 'key', 'value', 'typed_value', 'type', 'group', 'created_at', 'updated_at'])
        ->and($arr['typed_value'])->toBeTrue();
});

it('updates TenantSetting value and group cleanly', function () {
    $setting = TenantSetting::create(
        key: new SettingKey('currency'),
        value: 'USD',
        type: SettingType::string(),
        group: SettingGroup::general()
    );

    $setting->updateValue('CLP');
    expect($setting->value())->toBe('CLP')
        ->and($setting->typedValue())->toBe('CLP');
});
