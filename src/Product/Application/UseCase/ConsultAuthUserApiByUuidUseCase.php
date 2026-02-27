<?php


namespace Src\Product\Application\UseCase;

use Src\Product\Application\Contracts\AuthServices;
use Src\Product\Domain\Entities\AuthUser;
use Src\Product\Domain\ValueObjects\AvatarUrl;
use Src\Product\Domain\ValueObjects\UserEmail;
use Src\Product\Domain\ValueObjects\UserName;
use Src\Product\Domain\ValueObjects\UserType;
use Src\Product\Domain\ValueObjects\Uuid;

class ConsultAuthUserApiByUuidUseCase {


    public function __construct(
        protected AuthServices $auth_services
    ){}

    public function execute(Uuid $uuid, string $dominio=""):? AuthUser{
        $dataApi= $this->auth_services->consultAuthUserByUuid($uuid, $dominio);

        if($dataApi['code']!=200){
            return null;
        }

        $user_id= Uuid::make($dataApi["data"]["user_id"]);
        $user_name= UserName::make($dataApi["data"]["user_name"]);
        $user_email= UserEmail::make($dataApi["data"]["user_email"]);
        $user_type= UserType::make($dataApi["data"]["user_type"]);
        $user_avatar=($dataApi["data"]["user_avatar"]!=null && $dataApi["data"]["user_avatar"]!="" )? AvatarUrl::make($dataApi["data"]["user_avatar"]) :null;


        return AuthUser::reconstitute(
            $user_id,
            $user_name,
            $user_email,
            $user_type,
            $user_avatar
        );

    }



}


?>
