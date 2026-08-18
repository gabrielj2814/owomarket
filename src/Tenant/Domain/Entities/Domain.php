<?php

namespace Src\Tenant\Domain\Entities;

use Src\Shared\Domain\Contracts\UuidGenerator;
use Src\Shared\Domain\ValueObjects\CreatedAt;
use Src\Shared\Domain\ValueObjects\UpdatedAt;
use Src\Tenant\Domain\ValueObjects\Domain as ValueObjectsDomain;
use Src\Tenant\Domain\ValueObjects\DomainFallback;
use Src\Tenant\Domain\ValueObjects\DomainPrimary;
use Src\Tenant\Domain\ValueObjects\Uuid;

class Domain
{
    private Uuid $id;

    private Uuid $tenantId;

    private ValueObjectsDomain $domain;

    private DomainPrimary $is_primary;

    private DomainFallback $is_fallback;

    private ?CreatedAt $createdAt;

    private ?UpdatedAt $updatedAt;

    // Constructor privado
    private function __construct(
        Uuid $id,
        Uuid $tenantId,
        ValueObjectsDomain $domain,
        DomainPrimary $is_primary,
        DomainFallback $is_fallback,
        ?CreatedAt $createdAt = null,
        ?UpdatedAt $updatedAt = null,
    ) {
        $this->id = $id;
        $this->tenantId = $tenantId;
        $this->domain = $domain;
        $this->is_primary = $is_primary;
        $this->is_fallback = $is_fallback;
        $this->createdAt = $createdAt;
        $this->updatedAt = $updatedAt;
    }

    public static function create(
        UuidGenerator $generator,
        Uuid $tenantId,
        ValueObjectsDomain $domain,
        DomainPrimary $is_primary,
        DomainFallback $is_fallback,
    ): self {
        return new self(
            id: Uuid::generate($generator),
            tenantId: $tenantId,
            domain: $domain,
            is_primary: $is_primary,
            is_fallback: $is_fallback,
            createdAt: null,
            updatedAt: null,
        );
    }

    public static function reconstitute(
        Uuid $id,
        Uuid $tenantId,
        ValueObjectsDomain $domain,
        DomainPrimary $is_primary,
        DomainFallback $is_fallback,
        ?CreatedAt $createdAt,
        ?UpdatedAt $updatedAt,
    ): self {
        return new self(
            id: $id,
            tenantId: $tenantId,
            domain: $domain,
            is_primary: $is_primary,
            is_fallback: $is_fallback,
            createdAt: $createdAt,
            updatedAt: $updatedAt
        );
    }

    public function getId(): Uuid
    {
        return $this->id;
    }

    public function getTenantId(): Uuid
    {
        return $this->tenantId;
    }

    public function getDomain(): ValueObjectsDomain
    {
        return $this->domain;
    }

    public function isPrimary(): DomainPrimary
    {
        return $this->is_primary;
    }

    public function isFallback(): DomainFallback
    {
        return $this->is_fallback;
    }

    public function getCreatedAt(): ?CreatedAt
    {
        return $this->createdAt;
    }

    public function getUpdatedAt(): ?UpdatedAt
    {
        return $this->updatedAt;
    }
}
