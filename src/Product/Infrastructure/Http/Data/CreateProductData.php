<?php

namespace Src\Product\Infrastructure\Http\Data;

use Spatie\LaravelData\Data;

class CreateProductData extends Data {

    public function __construct(
        public string $name,
        public string $slug,
        public float $price,
        public string $sku
    ){}


}



?>
