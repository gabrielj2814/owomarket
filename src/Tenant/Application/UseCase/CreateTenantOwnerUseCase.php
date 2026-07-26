<?php


namespace Src\Tenant\Application\UseCase;

use App\Models\Tenant;
use Src\Shared\Domain\ValueObjects\CreatedAt;
use Src\Shared\Domain\ValueObjects\UpdatedAt;
use Src\Tenant\Application\Contracts\Repositories\TenantOwnerRepositoryInterface;
use Src\Tenant\Domain\Entities\TenantOwner;
use Src\Shared\Domain\Contracts\PasswordHasher;
use Src\Shared\Domain\Contracts\PasswordValidator;
use Src\Tenant\Domain\ValueObjects\AvatarUrl;
use Src\Tenant\Domain\ValueObjects\Password;
use Src\Tenant\Domain\ValueObjects\PhoneNumber;
use Src\Tenant\Domain\ValueObjects\UserEmail;
use Src\Tenant\Domain\ValueObjects\UserName;
use Src\Tenant\Domain\ValueObjects\UserStatus;
use Src\Tenant\Domain\ValueObjects\UserType;


class CreateTenantOwnerUseCase {



    /**
     * Constructor de la clase.
     */



    public function __construct(
        protected TenantOwnerRepositoryInterface $repository,
        protected PasswordValidator $validator,
        protected PasswordHasher $hasher
    ){}


    /**
     * Método execute.
     */


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
