<?php



namespace Src\Authentication\Infrastructure\Eloquent\Repositories;

use Src\Authentication\Application\Contracts\Repositories\UserRepositoryInterface;
use Src\Authentication\Domain\Entities\User as EntitiesUser;
use Src\Authentication\Domain\ValueObjects\Password;
use Src\Authentication\Domain\ValueObjects\UserEmail;
use Src\Authentication\Domain\ValueObjects\UserName;
use Src\Authentication\Domain\ValueObjects\UserStatus;
use Src\Authentication\Domain\ValueObjects\UserType;
use Src\Authentication\Domain\ValueObjects\Uuid;
use Src\Authentication\Infrastructure\Eloquent\Models\User;

class UserRepository implements UserRepositoryInterface
{
    //


    public function consultarPorMail(UserEmail $mail): ?EntitiesUser
    {
        $respuesta=User::where("email","=",$mail->value())->first();

        if(!$respuesta){
            return null;
        }

        return EntitiesUser::reconstitute(
            id:                 Uuid::make($respuesta->id),
            name:               UserName::make($respuesta->name),
            email:              UserEmail::make($respuesta->email),
            password:           Password::fromHash($respuesta->password),
            type:               UserType::make($respuesta->type),
            isActive:           UserStatus::make((bool)$respuesta->isActive)
        );
    }




}



?>
