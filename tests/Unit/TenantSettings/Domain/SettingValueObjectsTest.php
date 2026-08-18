<?php

declare(strict_types=1);

use Src\TenantSettings\Domain\Exceptions\InvalidSettingKeyException;
use Src\TenantSettings\Domain\ValueObjects\SettingGroup;
use Src\TenantSettings\Domain\ValueObjects\SettingId;
use Src\TenantSettings\Domain\ValueObjects\SettingKey;
use Src\TenantSettings\Domain\ValueObjects\SettingType;

it('SettingId generates valid random UUID or accepts valid string', function () {
    $id = SettingId::random();
    expect($id->value())->toBeString()
        ->and(SettingId::fromString($id->value())->equals($id))->toBeTrue();
});

it('SettingId throws exception for invalid UUID string', function () {
    new SettingId('invalid-uuid-123');
})->throws(InvalidArgumentException::class);

it('SettingKey accepts valid alphanumeric and symbol keys and normalizes to lowercase', function () {
    $key = new SettingKey('Store_Name.V2');
    expect($key->value())->toBe('store_name.v2')
        ->and((string) $key)->toBe('store_name.v2');
});

it('SettingKey throws InvalidSettingKeyException for invalid keys', function () {
    new SettingKey('invalid key with spaces & symbols!');
})->throws(InvalidSettingKeyException::class);

it('SettingType validates and casts values properly', function () {
    $strType = SettingType::string();
    expect($strType->value())->toBe('string')
        ->and($strType->castValue('hello'))->toBe('hello');

    $boolType = SettingType::boolean();
    expect($boolType->castValue('1'))->toBeTrue()
        ->and($boolType->castValue('true'))->toBeTrue()
        ->and($boolType->castValue('0'))->toBeFalse();

    $intType = SettingType::integer();
    expect($intType->castValue('42'))->toBe(42);

    $floatType = SettingType::float();
    expect($floatType->castValue('19.99'))->toBe(19.99);

    $jsonType = SettingType::json();
    expect($jsonType->castValue('{"a":1}'))->toBe(['a' => 1]);
});

it('SettingType throws exception for invalid type', function () {
    new SettingType('unknown_type');
})->throws(InvalidArgumentException::class);

it('SettingGroup validates allowed groups', function () {
    $group = SettingGroup::appearance();
    expect($group->value())->toBe('appearance')
        ->and($group->equals(SettingGroup::fromString('appearance')))->toBeTrue();
});

it('SettingGroup throws exception for invalid group', function () {
    new SettingGroup('invalid_group');
})->throws(InvalidArgumentException::class);
