<?php


namespace Src\Authentication\Application\Contracts;

use Src\Authentication\Domain\Entities\User;

interface UserServices {

    /**
     * Método consultUserByEmail.
     */

    public function consultUserByEmail(string $email, string $host = ""):array;

}



?>
