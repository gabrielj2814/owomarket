<?php

namespace Src\Authentication\Application\UseCase;

use Src\Authentication\Application\Contracts\Repositories\AuthUserRepositoryInterface;
use Src\Authentication\Domain\Entities\AuthUser;
use Src\Authentication\Domain\Entities\User;

class CrearAuthUserUseCase {



    public function __construct(
        protected AuthUserRepositoryInterface $auth_user_repository
    ){}

    public function execute(User $user):? AuthUser{
        return $this->auth_user_repository->crearte($user);
    }



}


?>
