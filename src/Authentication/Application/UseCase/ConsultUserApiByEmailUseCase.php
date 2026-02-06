<?php

namespace Src\Authentication\Application\UseCase;

use Src\Authentication\Application\Contracts\UserServices;
use Src\Authentication\Domain\Entities\User;
use Src\Authentication\Domain\ValueObjects\AvatarUrl;
use Src\Authentication\Domain\ValueObjects\UserEmail;
use Src\Authentication\Domain\ValueObjects\UserName;
use Src\Authentication\Domain\ValueObjects\UserStatus;
use Src\Authentication\Domain\ValueObjects\UserType;
use Src\Authentication\Domain\ValueObjects\Uuid;

class ConsultUserApiByEmailUseCase {



    public function __construct(
        protected UserServices $user_services
    ){}



    public function execute(UserEmail $email,string  $host = ""):?User {
        $dataApi=$this->user_services->consultUserByEmail($email->value(), $host);

        if($dataApi['code']!=200){
            return null;
        }

        $uuid= Uuid::make($dataApi['data']["id"]);
        $name= UserName::make($dataApi['data']["name"]);
        $emailApi= UserEmail::make($dataApi['data']["email"]);
        $password=null;
        $type=UserType::make($dataApi['data']["type"]);
        $is_active=UserStatus::make($dataApi['data']["is_active"]);
        $avatar=($dataApi['data']["avatar"]!=null && $dataApi['data']["avatar"]!="")? AvatarUrl::make($dataApi['data']["avatar"]) :null;
        return User::reconstitute(
            $uuid,
            $name,
            $emailApi,
            $password,
            $type,
            $is_active,
            $avatar
        );
    }




}



?>
