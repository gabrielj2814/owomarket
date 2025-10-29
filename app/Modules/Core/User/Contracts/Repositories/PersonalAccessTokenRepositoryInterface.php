<?php


namespace App\Modules\Core\User\Contracts\Repositories;

use App\Modules\Core\User\Models\User;


interface PersonalAccessTokenRepositoryInterface
{
    public function generarToken(User $user):?string;
    public function findToken(string $token): ?object;
    public function deleteToken(string $token): void;
}



?>
