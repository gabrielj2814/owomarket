<?php

namespace Src\User\Infrastructure\Eloquent\Repositories;

use Src\Shared\Domain\ValueObjects\CreatedAt;
use Src\Shared\Domain\ValueObjects\PhoneNumber;
use Src\Shared\Domain\ValueObjects\Timestamps;
use Src\Shared\Domain\ValueObjects\UpdatedAt;
use Src\Shared\Domain\ValueObjects\UserEmail;
use Src\Shared\Domain\ValueObjects\UserStatus;
use Src\Shared\Domain\ValueObjects\Uuid;
use Src\User\Application\Contracts\Repositories\UserRepositoryInterface;
use Src\User\Domain\Entities\User as EntitiesUser;
use Src\User\Domain\ValueObjects\AvatarUrl;
use Src\User\Domain\ValueObjects\EmailVerifiedAt;
use Src\User\Domain\ValueObjects\Password;
use Src\User\Domain\ValueObjects\PinVerification;
use Src\User\Domain\ValueObjects\RememberToken;
use Src\User\Domain\ValueObjects\UserName;
use Src\User\Domain\ValueObjects\UserType;
use Src\User\Infrastructure\Eloquent\Models\User;

class UserRepository implements UserRepositoryInterface
{
    //

    /**
     * Consulta un usuario en la base de datos a partir de su dirección de correo electrónico.
     * Reconstituye la entidad de dominio completa si lo encuentra.
     *
     * @param  UserEmail  $mail  Objeto de valor que contiene el email.
     * @return EntitiesUser|null La entidad de dominio del usuario, o null si no existe.
     */
    /**
     * Método consultarPorMail.
     */
    public function consultarPorMail(UserEmail $mail): ?EntitiesUser
    {
        $respuesta = User::where('email', '=', $mail->value())->first();

        if (! $respuesta) {
            return null;
        }

        $fechas = Timestamps::make(CreatedAt::fromString($respuesta->created_at), UpdatedAt::fromString($respuesta->updated_at));

        return EntitiesUser::reconstitute(
            id: Uuid::make($respuesta->id),
            name: UserName::make($respuesta->name),
            email: UserEmail::make($respuesta->email),
            password: Password::fromHash($respuesta->password),
            emailVerifiedAt: ($respuesta->email_verified_at !== null) ? EmailVerifiedAt::fromString($respuesta->email_verified_at) : null,
            pin: ($respuesta->pin != null) ? PinVerification::make($respuesta->pin) : null,
            type: UserType::make($respuesta->type),
            phone: ($respuesta->phone != null) ? PhoneNumber::make($respuesta->phone) : null,
            avatar: ($respuesta->avatar != null) ? AvatarUrl::make($respuesta->avatar) : null,
            isActive: UserStatus::make($respuesta->is_active),
            rememberToken: ($respuesta->rememberToken != null) ? RememberToken::make($respuesta->rememberToken) : null,
            createdAt: $fechas->createdAt(),
            updatedAt: $fechas->updatedAt(),
        );
    }
}
