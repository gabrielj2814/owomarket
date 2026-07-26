<?php

namespace Src\Product\Domain\Entities;

use Src\Shared\Domain\Contracts\UuidGenerator;
use Src\Product\Domain\ValueObjects\NameProduct;
use Src\Product\Domain\ValueObjects\PriceProduct;
use Src\Product\Domain\ValueObjects\Sku;
use Src\Product\Domain\ValueObjects\Slug;
use Src\Product\Domain\ValueObjects\Uuid;

class Product{


    private Uuid            $id;
    private NameProduct     $name;
    private Slug            $slug;
    private PriceProduct    $price;
    private Sku             $sku;


    private function __construct(
       Uuid             $id,
       NameProduct      $name,
       Slug             $slug,
       PriceProduct     $price,
       Sku              $sku
    ){
        $this->id       = $id;
        $this->name     = $name;
        $this->slug     = $slug;
        $this->price    = $price;
        $this->sku      = $sku;
    }

    public static function create(
        UuidGenerator    $generator,
        NameProduct      $name,
        Slug             $slug,
        PriceProduct     $price,
        Sku              $sku
    ): self{
        return new self(
            Uuid::generate($generator),
            $name,
            $slug,
            $price,
            $sku
        );
    }

    public static function reconstitute(
        Uuid             $id,
        NameProduct      $name,
        Slug             $slug,
        PriceProduct     $price,
        Sku              $sku
    ): self{
        return new self(
            $id,
            $name,
            $slug,
            $price,
            $sku
        );
    }



    public function getId(): Uuid {
        return $this->id;
    }

    public function getName(): NameProduct {
        return $this->name;
    }

    public function setName(NameProduct $name): void {
        $this->name = $name;
    }

    public function getSlug(): Slug {
        return $this->slug;
    }

    public function setSlug(Slug $slug): void {
        $this->slug = $slug;
    }

    public function getPrice(): PriceProduct {
        return $this->price;
    }

    public function setPrice(PriceProduct $price): void {
        $this->price = $price;
    }

    public function getSku(): Sku {
        return $this->sku;
    }

    public function setSku(Sku $sku): void {
        $this->sku = $sku;
    }


}


?>
