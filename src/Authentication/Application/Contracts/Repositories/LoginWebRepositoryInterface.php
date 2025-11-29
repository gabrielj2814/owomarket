<?php


namespace Src\Authentication\Application\Contracts\Repositories;

use Src\Authentication\Domain\ValueObjects\UserEmail;
use Src\Authentication\Domain\ValueObjects\Uuid;

interface LoginWebRepositoryInterface {

    public function loginWebUser(UserEmail $email): void;

    public function logoutWebUser(Uuid $uuid): bool;

}




?>
