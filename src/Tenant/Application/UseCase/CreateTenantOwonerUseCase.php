<?php


namespace Src\Tenant\Application\UseCase;

use App\Models\Tenant;
use Src\Shared\ValuesObjects\CreatedAt;
use Src\Shared\ValuesObjects\UpdatedAt;
use Src\Tenant\Application\Contracts\Repositories\TenantOwnerRepositoryInterface;
use Src\Tenant\Domain\Entities\TenantOwner;
use Src\Tenant\Domain\Shared\Security\PasswordHasher;
use Src\Tenant\Domain\Shared\Security\PasswordValidator;
use Src\Tenant\Domain\ValuesObjects\AvatarUrl;
use Src\Tenant\Domain\ValuesObjects\Password;
use Src\Tenant\Domain\ValuesObjects\PhoneNumber;
use Src\Tenant\Domain\ValuesObjects\UserEmail;
use Src\Tenant\Domain\ValuesObjects\UserName;
use Src\Tenant\Domain\ValuesObjects\UserStatus;
use Src\Tenant\Domain\ValuesObjects\UserType;


class CreateTenantOwonerUseCase {



    public function __construct(
        protected TenantOwnerRepositoryInterface $repository,
        protected PasswordValidator $validator,
        protected PasswordHasher $hasher
    ){}


    public function execute(string $nameUser, string $emailUser, string $phoneUser, string $passwordUser){

        $urlAvatarDefault="https://i.pinimg.com/originals/20/91/03/209103e917c549f89eda8c62d3fc34f3.jpg";
        $name=UserName::make($nameUser);
        $email=UserEmail::make($emailUser);
        $password=Password::fromPlainText(
            $passwordUser,
            $this->validator,
            $this->hasher
        );
        $phone=PhoneNumber::make($phoneUser);
        $type=UserType::make(UserType::TENANT_OWNER);
        $avatar=AvatarUrl::make($urlAvatarDefault);
        $status=UserStatus::active();

        $create_at= CreatedAt::now();
        $update_at= UpdatedAt::now();

        $emailVerifiedAt = null;
        $pin = null;

        $tenantOwner= TenantOwner::create(
            $name,
            $email,
            $password,
            $emailVerifiedAt,
            $pin,
            $type,
            $phone,
            $avatar,
            $status,
            $create_at,
            $update_at
        );

        $record= $this->repository->createTenantOwner($tenantOwner);

        return $tenantOwner;

    }




}



?>
