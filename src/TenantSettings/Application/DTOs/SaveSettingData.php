<?php

declare(strict_types=1);

namespace Src\TenantSettings\Application\DTOs;

final class SaveSettingData
{
    public function __construct(
        public readonly string $key,
        public readonly ?string $value = null,
        public readonly string $type = 'string',
        public readonly string $group = 'general'
    ) {}

    public static function fromArray(array $data): self
    {
        return new self(
            key: (string) ($data['key'] ?? ''),
            value: isset($data['value']) ? (string) $data['value'] : null,
            type: (string) ($data['type'] ?? 'string'),
            group: (string) ($data['group'] ?? 'general')
        );
    }
}
