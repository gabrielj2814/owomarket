<?php


namespace Src\Authentication\Application\Contracts;

interface UserServices {

    public function consultUserByEmail(string $email):void;

}



?>
