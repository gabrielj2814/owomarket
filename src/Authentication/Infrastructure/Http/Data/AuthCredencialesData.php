<?php


namespace Src\Authentication\Infrastructure\Http\Data;

use Spatie\LaravelData\Data;

class AuthCredencialesData extends Data
{
    public function __construct(
        public string $email,
        public string $password
    )
    {}
}



?>
