<?php


namespace Src\Authentication\Application\Contracts\Repositories;

use Src\Authentication\Domain\ValueObjects\UserEmail;

interface LoginWebRepositoryInterface {

    public function loginWebUser(UserEmail $email): void;

    public function logoutWebUser(): void;

}




?>
