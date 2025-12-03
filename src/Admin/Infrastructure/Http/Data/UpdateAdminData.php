<?php


namespace Src\Admin\Infrastructure\Http\Data;

use Spatie\LaravelData\Data;

class UpdateAdminData extends Data {


    public function __construct(
        public string $id,
        public string $name,
        public string $email,
        public string $phone,
    ){}



}



?>
