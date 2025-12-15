<?php


namespace Src\Tenant\Domain\ValuesObjects;

use InvalidArgumentException;

final class TenantName {

    private string $value;


    private function __construct(string $nombre)
    {
        $this->value=$nombre;
    }

    public static function make(string $name):self{
        self::validate($name);
        return new self($name);
    }

    private static function validate(string $value){

        if($value==""){
           throw new InvalidArgumentException("El nombre no puede estar vacio");
        }

        if(strlen($value)<=1){
           throw new InvalidArgumentException("El nombre debe tener minumo 2 caracteres");
        }
    }

    public function value():string{
        return $this->value;
    }





}


?>
