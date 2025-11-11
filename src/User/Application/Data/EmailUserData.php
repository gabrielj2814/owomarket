<?php

namespace Src\User\Application\Data;

use Spatie\LaravelData\Attributes\Validation\Email;
use Spatie\LaravelData\Data;

class EmailUserData extends Data{

    public function __construct(
        #[Email]
        public string $email
    ){}

}




?>
