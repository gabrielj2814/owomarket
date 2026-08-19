<?php

namespace Src\Product\Infrastructure\Http\Data;

use Spatie\LaravelData\Data;

class EditProductData extends Data
{
    public function __construct(
        public string $uuid,
        public string $name,
        public float $price,
        public string $sku,
        public string $slug
    ) {}

}
