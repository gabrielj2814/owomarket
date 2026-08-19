<?php

namespace Src\Admin\Application\UseCase;

use Src\Admin\Application\Contracts\Repositories\AdminRepositoryInterface;
use Src\Admin\Domain\Entities\Admin;
use Src\Admin\Domain\ValueObjects\AvatarUrl;
use Src\Admin\Domain\ValueObjects\Password;
use Src\Admin\Domain\ValueObjects\PhoneNumber;
use Src\Admin\Domain\ValueObjects\UserName;
use Src\Admin\Domain\ValueObjects\UserStatus;
use Src\Admin\Domain\ValueObjects\UserType;
use Src\Shared\Domain\Contracts\PasswordHasher;
use Src\Shared\Domain\Contracts\PasswordValidator;
use Src\Shared\Domain\Contracts\UuidGenerator;
use Src\Shared\Domain\ValueObjects\CreatedAt;
use Src\Shared\Domain\ValueObjects\UpdatedAt;
use Src\Shared\Domain\ValueObjects\UserEmail;

class CreateAdminUseCase
{
    /**
     * Constructor de la clase.
     */
    public function __construct(
        protected AdminRepositoryInterface $admin_repository,
        protected PasswordValidator $validator,
        protected PasswordHasher $hasher,
        protected UuidGenerator $generator
    ) {}

    /**
     * Método execute.
     */
    public function execute(string $nameUser, string $emailUser, string $phoneUser, string $passwordUser): ?Admin
    {
        $urlAvatarDefault = 'https://i.pinimg.com/originals/b0/ce/76/b0ce76f4cdb95ef13afa21a889adfc71.jpg';
        $name = UserName::make($nameUser);
        $email = UserEmail::make($emailUser);
        $password = Password::fromPlainText(
            $passwordUser,
            $this->validator,
            $this->hasher
        );
        $phone = PhoneNumber::make($phoneUser);
        $type = UserType::make(UserType::SUPER_ADMIN);
        $avatar = AvatarUrl::make($urlAvatarDefault);
        $state = UserStatus::active();

        $create_at = CreatedAt::now();
        $update_at = UpdatedAt::now();

        $admin = Admin::create(
            $this->generator,
            $name,
            $email,
            $password,
            null,
            null,
            $type,
            $phone,
            $avatar,
            $state,
            $create_at,
            $update_at
        );

        $record = $this->admin_repository->create($admin);

        return $admin;

    }
}
