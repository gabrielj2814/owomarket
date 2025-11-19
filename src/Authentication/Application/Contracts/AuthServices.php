<?php


namespace Src\Authentication\Application\Contracts;

use Src\Authentication\Domain\Entities\User;
use Src\Authentication\Domain\ValueObjects\UserEmail;

interface AuthServices {

    public function consultUserByEmail(UserEmail $email):? User;

}

?>
