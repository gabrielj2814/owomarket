<?php


namespace Src\Tenant\Domain\Entities;

use Src\Shared\ValuesObjects\BoolValueObject;
use Src\Shared\ValuesObjects\CreatedAt;
use Src\Shared\ValuesObjects\UpdatedAt;
use Src\Tenant\Domain\ValuesObjects\Domain as ValuesObjectsDomain;
use Src\Tenant\Domain\ValuesObjects\Uuid;

class Domain {

    private Uuid                     $id;
    private Uuid                     $tenantId;
    private ValuesObjectsDomain      $domain;
    private BoolValueObject          $is_primary;
    private BoolValueObject          $is_fallback;
    private ?CreatedAt               $createdAt;
    private ?UpdatedAt               $updatedAt;

    private function __construct(
        Uuid                    $id,
        Uuid                    $tenantId,
        ValuesObjectsDomain     $domain,
        BoolValueObject         $is_primary,
        BoolValueObject         $is_fallback,
        ?CreatedAt              $createdAt,
        ?UpdatedAt              $updatedAt,
    ){
        $this->id               =$id;
        $this->tenantId         =$tenantId;
        $this->domain           =$domain;
        $this->is_primary       =$is_primary;
        $this->is_fallback      =$is_fallback;
        $this->createdAt        = $createdAt;
        $this->updatedAt        = $updatedAt;
    }

    public static function create(
        Uuid                    $tenantId,
        ValuesObjectsDomain     $domain,
        BoolValueObject         $is_primary,
        BoolValueObject         $is_fallback,
    ): self {
        return new self(
            id: Uuid::generate(),
            tenantId: $tenantId,
            domain: $domain,
            is_primary: $is_primary,
            is_fallback: $is_fallback,
            createdAt: null,
            updatedAt: null,
        );
    }

    public static function reconstitute(
        Uuid                    $id,
        Uuid                    $tenantId,
        ValuesObjectsDomain     $domain,
        BoolValueObject         $is_primary,
        BoolValueObject         $is_fallback,
        ?CreatedAt              $createdAt,
        ?UpdatedAt              $updatedAt,
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

    public function getDomain(): ValuesObjectsDomain
    {
        return $this->domain;
    }

    public function isPrimary(): BoolValueObject
    {
        return $this->is_primary;
    }

    public function isFallback(): BoolValueObject
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


?>
