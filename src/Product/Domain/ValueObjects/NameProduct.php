<?php

namespace Src\Product\Domain\ValueObjects;

use InvalidArgumentException;
use Src\Shared\ValuesObjects\StringValueObject;

final class NameProduct extends StringValueObject {


    public static function make(string $value): self
    {
        return new self($value);
    }

    public function validate(string $value): void
    {
        if($value){
            if (strlen($value) < 3 || strlen($value) > 255) {
                throw new InvalidArgumentException('El nombre del producto debe tener minimo 3 y maximo 255 caracteres.', 400);
            }
        } else {
            throw new InvalidArgumentException('El nombre del producto no puede estar vacío.', 400);
        }
    }



}


?>
