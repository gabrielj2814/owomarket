<?php

namespace Src\Admin\Application\UseCase;

use Src\Admin\Application\Contracts\Repositories\AdminRepositoryInterface;
use Src\Admin\Domain\Etities\Admin;
use Src\Admin\Domain\Shared\Security\PasswordHasher;
use Src\Admin\Domain\Shared\Security\PasswordValidator;
use Src\Admin\Domain\ValueObjects\AvatarUrl;
use Src\Admin\Domain\ValueObjects\Password;
use Src\Admin\Domain\ValueObjects\PhoneNumber;
use Src\Admin\Domain\ValueObjects\UserEmail;
use Src\Admin\Domain\ValueObjects\UserName;
use Src\Admin\Domain\ValueObjects\UserStatus;
use Src\Admin\Domain\ValueObjects\UserType;
use Src\Shared\ValuesObjects\CreatedAt;
use Src\Shared\ValuesObjects\UpdatedAt;

class CreateAdminUseCase {


    public function __construct(
        protected AdminRepositoryInterface $admin_repository,
        protected PasswordValidator $validator,
        protected PasswordHasher $hasher
    ){}

    public function execute(string $nameUser, string $emailUser, string $phoneUser, string $passwordUser): ?Admin{
        $urlAvatarDefault="https://i.pinimg.com/originals/b0/ce/76/b0ce76f4cdb95ef13afa21a889adfc71.jpg";
        $name=UserName::make($nameUser);
        $email=UserEmail::make($emailUser);
        $password=Password::fromPlainText(
            $passwordUser,
            $this->validator,
            $this->hasher
        );
        $phone=PhoneNumber::make($phoneUser);
        $type=UserType::make(UserType::SUPER_ADMIN);
        $avatar=AvatarUrl::make($urlAvatarDefault);
        $state=UserStatus::active();

        $create_at= CreatedAt::now();
        $update_at= UpdatedAt::now();

        $admin= Admin::create(
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

        $record= $this->admin_repository->create($admin);

        return $admin;

    }


}




?>
