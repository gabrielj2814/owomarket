<?php


namespace App\Modules\Core\User\Repositories;

use App\Modules\Core\User\Contracts\Repositories\PersonalAccessTokenRepositoryInterface;
use App\Modules\Core\User\Models\User;
use Laravel\Sanctum\PersonalAccessToken;

class PersonalAccessTokenRepository implements PersonalAccessTokenRepositoryInterface
{
    //

    /**
     * Genera un nuevo token de acceso personal (PAT) para el usuario.
     *
     * @param User $user El usuario para el que se generará el token.
     * @return string|null El token generado (en texto plano) o null en caso de error.
     */
    public function generarToken(User $user): ?string
    {
        $token = $user->createToken($user->id, ['*'], now()->addWeek())->plainTextToken;
        return $token;
    }

    /**
     * Busca un token de acceso personal a partir de su valor en texto plano.
     *
     * @param string $token El token de acceso (plain text).
     * @return object|null El objeto PersonalAccessToken si se encuentra, null en caso contrario.
     */
    public function findToken(string $token): ?object
    {
        $personalAccessToken = PersonalAccessToken::findToken($token);
        return $personalAccessToken;
    }

    /**
     * Elimina/Revoca un token de acceso personal.
     *
     * @param string $token El token de acceso (plain text) a eliminar.
     * @return void
     */
    public function deleteToken(string $token): void
    {
        $personalAccessToken = PersonalAccessToken::findToken($token);
        if ($personalAccessToken) {
            $personalAccessToken->delete();
        }
    }

}



?>
