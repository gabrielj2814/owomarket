<?php

namespace Src\Admin\Application\UseCase;

use Src\Admin\Application\Contracts\AuthServices;
use Src\Shared\Domain\Entities\AuthUser;
use Src\Shared\Domain\ValueObjects\AvatarUrl;
use Src\Shared\Domain\ValueObjects\UserEmail;
use Src\Shared\Domain\ValueObjects\UserName;
use Src\Shared\Domain\ValueObjects\UserType;
use Src\Shared\Domain\ValueObjects\Uuid;

class ConsultAuthUserApiByUuid
{
    /**
     * Constructor de la clase.
     */
    public function __construct(
        protected AuthServices $auth_services
    ) {}

    /**
     * Método execute.
     */
    public function execute(Uuid $uuid): ?AuthUser
    {

        $dataApi = $this->auth_services->consultAuthUserByUuid($uuid);

        if ($dataApi['code'] != 200) {
            return null;
        }

        $user_id = Uuid::make($dataApi['data']['user_id']);
        $user_name = UserName::make($dataApi['data']['user_name']);
        $user_email = UserEmail::make($dataApi['data']['user_email']);
        $user_type = UserType::make($dataApi['data']['user_type']);
        $user_avatar = ($dataApi['data']['user_avatar'] != null && $dataApi['data']['user_avatar'] != '') ? AvatarUrl::make($dataApi['data']['user_avatar']) : null;

        return AuthUser::reconstitute(
            null,
            $user_id,
            $user_name,
            $user_email,
            $user_type,
            $user_avatar
        );

    }
}
