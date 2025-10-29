<?php


namespace App\Modules\Core\Auth\Data;

use Spatie\LaravelData\Data;

class AurhCredencialesData extends Data
{
    public function __construct(
        public string $email,
        public string $password
    )
    {}
}



?>
