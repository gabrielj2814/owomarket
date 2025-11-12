<?php



namespace Src\User\Infrastructure\Eloquent\Repositories;

use Src\User\Application\Contracts\Repositories\UserRepositoryInterface;
use Src\User\Domain\Entities\User as EntitiesUser;
use Src\User\Domain\ValueObjects\Password;
use Src\User\Domain\ValueObjects\UserEmail;
use Src\User\Domain\ValueObjects\UserName;
use Src\User\Domain\ValueObjects\Uuid;
use Src\User\Infrastructure\Models\User;

class UserRepository implements UserRepositoryInterface
{
    //


    public function consultarPorMail(UserEmail $mail): ?EntitiesUser
    {
        $respuesta=User::where("email","=",$mail->value())->first();

        return EntitiesUser::reconstitute(
            id: Uuid::make($respuesta->id),
            name: UserName::make($respuesta->name),
            email:UserEmail::make($respuesta->email),
            password: Password::fromHash($respuesta->password)
        );
    }




}



?>
