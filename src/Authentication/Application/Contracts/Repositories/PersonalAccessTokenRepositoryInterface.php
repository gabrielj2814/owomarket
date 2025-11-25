<?php


namespace Src\Authentication\Application\Contracts\Repositories;

use Src\Authentication\Domain\Entities\User;

interface PersonalAccessTokenRepositoryInterface
{
    public function generarToken(User $user):?string;
    public function findToken(string $token): ?object;
    public function deleteToken(string $token): void;
}



?>
