<?php


namespace Src\Admin\Infrastructure\Http\Data;

use Spatie\LaravelData\Data;

class CreateAdminData extends Data {


    public function __construct(
        public string $name,
        public string $email,
        public string $phone,
    ){}
}


?>
