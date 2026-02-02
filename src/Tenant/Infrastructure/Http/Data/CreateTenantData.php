<?php


namespace Src\Tenant\Infrastructure\Http\Data;

use Spatie\LaravelData\Data;

class CreateTenantData extends Data {

    public function __construct(
        public string $store_name,
    ){}
}



?>
