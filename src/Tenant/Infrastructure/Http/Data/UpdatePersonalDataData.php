<?php


namespace Src\Tenant\Infrastructure\Http\Data;

use Spatie\LaravelData\Data;

class UpdatePersonalDataData extends Data {


    public function __construct(
        public readonly string $name,
        public readonly string $phone,
    ) {}


}


?>
