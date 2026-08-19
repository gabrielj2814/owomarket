<?php

namespace Src\Authentication\Infrastructure\Eloquent\Repositories;

use Src\Authentication\Application\Contracts\Repositories\UserRepositoryInterface;
use Src\Authentication\Domain\Entities\User as EntitiesUser;
use Src\Authentication\Domain\ValueObjects\UserType;
use Src\Authentication\Infrastructure\Eloquent\Models\User;
use Src\Shared\Domain\ValueObjects\AvatarUrl;
use Src\Shared\Domain\ValueObjects\Password;
use Src\Shared\Domain\ValueObjects\UserEmail;
use Src\Shared\Domain\ValueObjects\UserName;
use Src\Shared\Domain\ValueObjects\UserStatus;
use Src\Shared\Domain\ValueObjects\Uuid;

class UserRepository implements UserRepositoryInterface
{
    //

    /**
     * Método consultarPorMail.
     */
    public function consultarPorMail(UserEmail $mail): ?EntitiesUser
    {
        $respuesta = User::where('email', '=', $mail->value())->first();

        if (! $respuesta) {
            return null;
        }

        $avatar = ($respuesta->avatar != null && $respuesta->avatar != '') ? AvatarUrl::make($respuesta->avatar) : null;

        return EntitiesUser::reconstitute(
            id: Uuid::make($respuesta->id),
            name: UserName::make($respuesta->name),
            email: UserEmail::make($respuesta->email),
            password: Password::fromHash($respuesta->password),
            type: UserType::make($respuesta->type),
            isActive: UserStatus::make($respuesta->is_active),
            avatar: $avatar
        );
    }

    /**
     * Método generarToken.
     */
    public function generarToken(UserEmail $mail): ?string
    {
        $user = User::where('email', '=', $mail->value())->first();

        if (! $user) {
            return null;
        }

        $token = $user->createToken('auth_token')->plainTextToken;

        return $token;
    }
}
