<?php

namespace Src\Product\Domain\Entities;

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
        NameProduct      $name,
        Slug             $slug,
        PriceProduct     $price,
        Sku              $sku
    ): self{
        return new self(
            Uuid::generate(),
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

    public function getSlug(): Slug {
        return $this->slug;
    }

    public function getPrice(): PriceProduct {
        return $this->price;
    }

    public function getSku(): Sku {
        return $this->sku;
    }


}


?>
