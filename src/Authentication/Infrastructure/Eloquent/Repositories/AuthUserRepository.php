<?php


namespace Src\Authentication\Infrastructure\Eloquent\Repositories;

use Src\Authentication\Application\Contracts\Repositories\AuthUserRepositoryInterface;
use Src\Authentication\Domain\Entities\AuthUser;
use Src\Authentication\Domain\Entities\User;
use Src\Authentication\Domain\ValueObjects\AvatarUrl;
use Src\Authentication\Domain\ValueObjects\UserEmail;
use Src\Authentication\Domain\ValueObjects\UserName;
use Src\Authentication\Domain\ValueObjects\UserType;
use Src\Authentication\Domain\ValueObjects\Uuid;
use Src\Authentication\Infrastructure\Eloquent\Models\AuthUser as ModelsAuthUser;

// use Src\Authentication\Infrastructure\Eloquent\Models\AuthUser;

class AuthUserRepository implements AuthUserRepositoryInterface{



    public function crearte(User $user):? AuthUser{
        $avatar=($user->getAvatar()->value()!=null)?$user->getAvatar():null;
        $AuthUser=AuthUser::create(
            $user->getId(),
            $user->getName(),
            $user->getEmail(),
            $user->getType(),
            $avatar,
        );

        ModelsAuthUser::create([
            "id"           => $AuthUser->getId()->value(),
            "user_id"      => $AuthUser->getUserId()->value(),
            "user_name"    => $AuthUser->getName()->value(),
            "user_email"   => $AuthUser->getEmail()->value(),
            "user_type"    => $AuthUser->getType()->value(),
            "user_avatar"  => $AuthUser->getAvatar()->value(),
        ]);

        return $AuthUser;
    }

    public function consult(Uuid $uuid):? AuthUser{
        $respuesta= ModelsAuthUser::query()->where("user_id","=",$uuid->value())->first();
        if(!$respuesta){
            return null;
        }

        $id= Uuid::make($respuesta->id);
        $user_id= Uuid::make($respuesta->user_id);
        $user_name= UserName::make($respuesta->user_name);
        $user_email= UserEmail::make($respuesta->user_email);
        $user_type= UserType::make($respuesta->user_type);
        $user_avatar=($respuesta->user_avatar!=null && $respuesta->user_avatar!="" )? AvatarUrl::make($respuesta->user_avatar) :null;


        return AuthUser::reconstitute(
            $id,
            $user_id,
            $user_name,
            $user_email,
            $user_type,
            $user_avatar
        );
    }

    public function delete(Uuid $uuid): void{
        $registro=ModelsAuthUser::query()->where("id","=",$uuid->value())->first();
        if($registro){
            $registro->delete();
        }

    }




}


?>
