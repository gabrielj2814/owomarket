<?php

declare(strict_types=1);

namespace Src\TenantSettings\Domain\Entities;

use DateTimeImmutable;
use Src\TenantSettings\Domain\ValueObjects\SettingGroup;
use Src\TenantSettings\Domain\ValueObjects\SettingId;
use Src\TenantSettings\Domain\ValueObjects\SettingKey;
use Src\TenantSettings\Domain\ValueObjects\SettingType;

final class TenantSetting
{
    private SettingId $id;

    private SettingKey $key;

    private ?string $value;

    private SettingType $type;

    private SettingGroup $group;

    private ?DateTimeImmutable $createdAt;

    private ?DateTimeImmutable $updatedAt;

    public function __construct(
        SettingId $id,
        SettingKey $key,
        ?string $value = null,
        ?SettingType $type = null,
        ?SettingGroup $group = null,
        ?DateTimeImmutable $createdAt = null,
        ?DateTimeImmutable $updatedAt = null
    ) {
        $this->id = $id;
        $this->key = $key;
        $this->value = $value;
        $this->type = $type ?? SettingType::string();
        $this->group = $group ?? SettingGroup::general();
        $this->createdAt = $createdAt ?? new DateTimeImmutable;
        $this->updatedAt = $updatedAt ?? new DateTimeImmutable;
    }

    public static function create(
        SettingKey $key,
        ?string $value = null,
        ?SettingType $type = null,
        ?SettingGroup $group = null,
        ?SettingId $id = null
    ): self {
        $now = new DateTimeImmutable;

        return new self(
            id: $id ?? SettingId::random(),
            key: $key,
            value: $value,
            type: $type ?? SettingType::string(),
            group: $group ?? SettingGroup::general(),
            createdAt: $now,
            updatedAt: $now
        );
    }

    public function updateValue(?string $value, ?SettingType $type = null, ?SettingGroup $group = null): void
    {
        $this->value = $value;
        if ($type !== null) {
            $this->type = $type;
        }
        if ($group !== null) {
            $this->group = $group;
        }
        $this->updatedAt = new DateTimeImmutable;
    }

    public function id(): SettingId
    {
        return $this->id;
    }

    public function key(): SettingKey
    {
        return $this->key;
    }

    public function value(): ?string
    {
        return $this->value;
    }

    public function type(): SettingType
    {
        return $this->type;
    }

    public function group(): SettingGroup
    {
        return $this->group;
    }

    public function typedValue(): mixed
    {
        return $this->type->castValue($this->value);
    }

    public function createdAt(): ?DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function updatedAt(): ?DateTimeImmutable
    {
        return $this->updatedAt;
    }

    public function toArray(): array
    {
        return [
            'id' => $this->id->value(),
            'key' => $this->key->value(),
            'value' => $this->value,
            'typed_value' => $this->typedValue(),
            'type' => $this->type->value(),
            'group' => $this->group->value(),
            'created_at' => $this->createdAt?->format('Y-m-d H:i:s'),
            'updated_at' => $this->updatedAt?->format('Y-m-d H:i:s'),
        ];
    }
}
