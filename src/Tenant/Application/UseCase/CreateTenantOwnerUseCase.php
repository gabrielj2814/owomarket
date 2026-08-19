<?php

namespace Src\Tenant\Application\UseCase;

use Src\Shared\Domain\Contracts\PasswordHasher;
use Src\Shared\Domain\Contracts\PasswordValidator;
use Src\Shared\Domain\Contracts\UuidGenerator;
use Src\Shared\Domain\ValueObjects\AvatarUrl;
use Src\Shared\Domain\ValueObjects\PhoneNumber;
use Src\Shared\Domain\ValueObjects\UserEmail;
use Src\Shared\Domain\ValueObjects\UserName;
use Src\Shared\Domain\ValueObjects\UserStatus;
use Src\Tenant\Application\Contracts\Repositories\TenantOwnerRepositoryInterface;
use Src\Tenant\Domain\Entities\TenantOwner;
use Src\Tenant\Domain\ValueObjects\Password;
use Src\Tenant\Domain\ValueObjects\UserType;

class CreateTenantOwnerUseCase
{
    /**
     * Constructor de la clase.
     */
    public function __construct(
        protected TenantOwnerRepositoryInterface $repository,
        protected PasswordValidator $validator,
        protected PasswordHasher $hasher,
        protected UuidGenerator $generator
    ) {}

    /**
     * Método execute.
     */
    public function execute(string $nameUser, string $emailUser, string $phoneUser, string $passwordUser)
    {

        $urlAvatarDefault = 'https://i.pinimg.com/originals/20/91/03/209103e917c549f89eda8c62d3fc34f3.jpg';
        $name = UserName::make($nameUser);
        $email = UserEmail::make($emailUser);
        $password = Password::fromPlainText(
            $passwordUser,
            $this->validator,
            $this->hasher
        );
        $phone = PhoneNumber::make($phoneUser);
        $type = UserType::make(UserType::TENANT_OWNER);
        $avatar = AvatarUrl::make($urlAvatarDefault);
        $status = UserStatus::active();

        $emailVerifiedAt = null;
        $pin = null;

        // La entidad TenantOwner establece internamente createdAt y updatedAt.
        $tenantOwner = TenantOwner::create(
            $this->generator,
            $name,
            $email,
            $password,
            $emailVerifiedAt,
            $pin,
            $type,
            $phone,
            $avatar,
            $status
        );

        $record = $this->repository->createTenantOwner($tenantOwner);

        return $tenantOwner;

    }
}
