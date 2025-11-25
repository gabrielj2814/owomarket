<?php


namespace Src\Authentication\Infrastructure\Eloquent\Repositories;

use Laravel\Sanctum\PersonalAccessToken;
use Src\Authentication\Application\Contracts\Repositories\PersonalAccessTokenRepositoryInterface;
use Src\Authentication\Domain\Entities\User;
use Src\Authentication\Infrastructure\Eloquent\Models\User as ModelsUser;

class PersonalAccessTokenRepository implements PersonalAccessTokenRepositoryInterface
{
    //

    public function generarToken(User $user): ?string
    {
        $usuario=ModelsUser::where("id","=",$user->getId()->value())->first();
        $token = $usuario->createToken($user->getId()->value(), ['*'], now()->addWeek())->plainTextToken;
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
