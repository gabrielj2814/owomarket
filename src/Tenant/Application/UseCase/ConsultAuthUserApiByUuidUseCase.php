<?php

namespace Src\Tenant\Application\UseCase;

use Src\Tenant\Application\Contracts\AuthServices;
use Src\Tenant\Domain\Entities\AuthUser;
use Src\Tenant\Domain\ValueObjects\AvatarUrl;
use Src\Tenant\Domain\ValueObjects\UserEmail;
use Src\Tenant\Domain\ValueObjects\UserName;
use Src\Tenant\Domain\ValueObjects\UserType;
use Src\Tenant\Domain\ValueObjects\Uuid;

class ConsultAuthUserApiByUuidUseCase
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
    public function execute(Uuid $uuid, string $dominio = ''): ?AuthUser
    {
        $dataApi = $this->auth_services->consultAuthUserByUuid($uuid, $dominio);

        if ($dataApi['code'] != 200) {
            return null;
        }

        $user_id = Uuid::make($dataApi['data']['user_id']);
        $user_name = UserName::make($dataApi['data']['user_name']);
        $user_email = UserEmail::make($dataApi['data']['user_email']);
        $user_type = UserType::make($dataApi['data']['user_type']);
        $user_avatar = ($dataApi['data']['user_avatar'] != null && $dataApi['data']['user_avatar'] != '') ? AvatarUrl::make($dataApi['data']['user_avatar']) : null;

        return AuthUser::reconstitute(
            $user_id,
            $user_name,
            $user_email,
            $user_type,
            $user_avatar
        );

    }
}
