<?php

namespace Src\Product\Domain\ValueObjects;

use InvalidArgumentException;
use Src\Shared\ValuesObjects\FloatValueObject;

class PriceProduct extends FloatValueObject {


    public static function make(float $value): self
    {
        return new self($value);
    }

    public function validate(float $value): void
    {
        if($value){
            if ($value < 0) {
                throw new InvalidArgumentException('El precio del producto no puede ser negativo.', 400);
            }
        }
        else {
            throw new InvalidArgumentException('El precio del producto no puede estar vacío.', 400);
        }
    }



}


?>
