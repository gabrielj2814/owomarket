<?php


namespace Src\Authentication\Application\Contracts\Repositories;

use Src\Authentication\Domain\Entities\User;

interface PersonalAccessTokenRepositoryInterface
{
    /**
     * Método generarToken.
     */
    public function generarToken(User $user):?string;
    /**
     * Método findToken.
     */
    public function findToken(string $token): ?object;
    /**
     * Método deleteToken.
     */
    public function deleteToken(string $token): void;
}



?>
