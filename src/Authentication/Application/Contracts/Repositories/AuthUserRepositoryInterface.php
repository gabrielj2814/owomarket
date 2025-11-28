<?php


namespace Src\Authentication\Application\Contracts\Repositories;

use Src\Authentication\Domain\Entities\AuthUser;
use Src\Authentication\Domain\Entities\User;
use Src\Authentication\Domain\ValueObjects\Uuid;

interface AuthUserRepositoryInterface {

    public function crearte(User $user):? AuthUser;

    public function consult(Uuid $uuid):? AuthUser;

    public function delete(Uuid $uuid): void;


}


?>
