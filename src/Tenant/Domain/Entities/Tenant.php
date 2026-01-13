<?php


namespace Src\Tenant\Domain\Entities;

use Src\Shared\Collection\Collection;
use Src\Shared\ValuesObjects\CreatedAt;
use Src\Shared\ValuesObjects\Currency;
use Src\Shared\ValuesObjects\SoftDeleteAt;
use Src\Shared\ValuesObjects\Timezone;
use Src\Shared\ValuesObjects\UpdatedAt;
use Src\Tenant\Domain\ValuesObjects\Slug;
use Src\Tenant\Domain\ValuesObjects\TenantName;
use Src\Tenant\Domain\ValuesObjects\TenantRequest;
use Src\Tenant\Domain\ValuesObjects\TenantStatus;
use Src\Tenant\Domain\ValuesObjects\Uuid;

class Tenant {


    private Uuid            $id;
    private TenantName      $name;
    private Slug            $slug;
    private TenantStatus    $status;
    private Timezone        $timezone;
    private Currency        $currency;
    private TenantRequest   $request;
    private ?CreatedAt      $createdAt;
    private ?UpdatedAt      $updatedAt;
    private ?SoftDeleteAt   $softdeleteAt;

    private Collection      $owners;

    private function __construct(
        Uuid                    $id,
        TenantName              $name,
        Slug                    $slug,
        TenantStatus            $status,
        Timezone                $timezone,
        Currency                $currency,
        TenantRequest           $request,
        ?CreatedAt              $createdAt,
        ?UpdatedAt              $updatedAt,
        ?SoftDeleteAt           $softdeleteAt,
    ){
        $this->id               =$id;
        $this->name             =$name;
        $this->slug             =$slug;
        $this->status           =$status;
        $this->timezone         =$timezone;
        $this->currency         =$currency;
        $this->request          =$request;
        $this->createdAt        = $createdAt;
        $this->updatedAt        = $updatedAt;
        $this->softdeleteAt     = $softdeleteAt;
    }

    public static function create(
        TenantName      $name,
        Slug            $slug,
        TenantStatus    $status,
        Timezone        $timezone,
        Currency        $currency,
        TenantRequest   $request
    ): self{
        return new self(
            Uuid::generate(),
            $name,
            $slug,
            $status,
            $timezone,
            $currency,
            $request,
            CreatedAt::now(),
            UpdatedAt::now(),
            null
        );
    }

    public static function reconstitute(
        Uuid            $id,
        TenantName      $name,
        Slug            $slug,
        TenantStatus    $status,
        Timezone        $timezone,
        Currency        $currency,
        TenantRequest   $request,
        CreatedAt       $createdAt,
        UpdatedAt       $updatedAt,
        ?SoftDeleteAt   $softdeleteAt,
    ): self
    {
        return new self(
            $id,
            $name,
            $slug,
            $status,
            $timezone,
            $currency,
            $request,
            $createdAt,
            $updatedAt,
            $softdeleteAt,
        );
    }

    public function setOwners(Collection $owners){
        $this->owners= $owners;
    }

    public function getOwners(): Collection{
        return $this->owners;
    }

    public function getId(): Uuid
    {
        return $this->id;
    }

    public function getName(): TenantName
    {
        return $this->name;
    }

    public function getSlug(): Slug
    {
        return $this->slug;
    }

    public function getStatus(): TenantStatus
    {
        return $this->status;
    }

    public function getTimezone(): Timezone
    {
        return $this->timezone;
    }

    public function getCurrency(): Currency
    {
        return $this->currency;
    }

    public function getRequest(): TenantRequest
    {
        return $this->request;
    }

    public function getCreatedAt(): ?CreatedAt
    {
        return $this->createdAt;
    }

    public function getUpdatedAt(): ?UpdatedAt
    {
        return $this->updatedAt;
    }

    public function getSoftdeleteAt(): ?SoftDeleteAt
    {
        return $this->softdeleteAt;
    }

    public function suspended(){
        $this->status=TenantStatus::suspended();
        $this->updatedAt=UpdatedAt::now();
    }

    public function inactive(){
        $this->status=TenantStatus::inactive();
        $this->updatedAt=UpdatedAt::now();
    }

    public function active(){
        $this->status=TenantStatus::active();
        $this->updatedAt=UpdatedAt::now();
    }

    public function rejectedRequest(){
        $this->request=TenantRequest::rejected();
        $this->updatedAt=UpdatedAt::now();
    }

    public function approvedRequest(){
        $this->request=TenantRequest::approved();
        $this->updatedAt=UpdatedAt::now();
    }

}



?>
