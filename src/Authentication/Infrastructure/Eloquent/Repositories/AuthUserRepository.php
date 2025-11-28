<?php


namespace Src\Authentication\Infrastructure\Eloquent\Repositories;

use Src\Authentication\Application\Contracts\Repositories\AuthUserRepositoryInterface;
use Src\Authentication\Domain\Entities\AuthUser;
use Src\Authentication\Domain\Entities\User;
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
        return ModelsAuthUser::query()->where("id","=",$uuid->value())->first();
    }

    public function delete(Uuid $uuid): void{
        $registro=ModelsAuthUser::query()->where("id","=",$uuid->value())->first();
        if($registro){
            $registro->delete();
        }

    }




}


?>
