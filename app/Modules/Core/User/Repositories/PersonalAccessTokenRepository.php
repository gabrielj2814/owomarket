<?php


namespace App\Modules\Core\User\Repositories;

use App\Modules\Core\User\Contracts\Repositories\PersonalAccessTokenRepositoryInterface;
use App\Modules\Core\User\Models\User;
use Laravel\Sanctum\PersonalAccessToken;

class PersonalAccessTokenRepository implements PersonalAccessTokenRepositoryInterface
{
    //

    public function generarToken(User $user): ?string
    {
        $token = $user->createToken($user->id, ['*'], now()->addWeek())->plainTextToken;
        return $token;
    }

    public function findToken(string $token): ?object
    {
        $personalAccessToken = PersonalAccessToken::findToken($token);
        return $personalAccessToken;
    }

    public function deleteToken(string $token): void
    {
        $personalAccessToken = PersonalAccessToken::findToken($token);
        if ($personalAccessToken) {
            $personalAccessToken->delete();
        }
    }

}



?>
