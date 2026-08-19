<?php

namespace Src\Shared\Domain\Entities;

use Src\Shared\Domain\Contracts\UuidGenerator;
use Src\Shared\Domain\ValueObjects\AvatarUrl;
use Src\Shared\Domain\ValueObjects\UserEmail;
use Src\Shared\Domain\ValueObjects\UserName;
use Src\Shared\Domain\ValueObjects\UserType;
use Src\Shared\Domain\ValueObjects\Uuid;

final class AuthUser
{
    private function __construct(
        private ?Uuid $id,
        private Uuid $user_id,
        private UserName $name,
        private UserEmail $email,
        private UserType $type,
        private ?AvatarUrl $avatar
    ) {}

    public static function create(
        UuidGenerator $generator,
        Uuid $user_id,
        UserName $name,
        UserEmail $email,
        UserType $type,
        ?AvatarUrl $avatar = null
    ): self {
        return new self(
            Uuid::generate($generator),
            $user_id,
            $name,
            $email,
            $type,
            $avatar
        );
    }

    public static function reconstitute(
        ?Uuid $id,
        Uuid $user_id,
        UserName $name,
        UserEmail $email,
        UserType $type,
        ?AvatarUrl $avatar = null
    ): self {
        return new self($id, $user_id, $name, $email, $type, $avatar);
    }

    public function getId(): ?Uuid
    {
        return $this->id;
    }

    public function getUserId(): Uuid
    {
        return $this->user_id;
    }

    public function getName(): UserName
    {
        return $this->name;
    }

    public function getEmail(): UserEmail
    {
        return $this->email;
    }

    public function getType(): UserType
    {
        return $this->type;
    }

    public function getAvatar(): ?AvatarUrl
    {
        return $this->avatar;
    }

    public function isSuperAdmin(): bool
    {
        return $this->type->isSuperAdmin();
    }

    public function isTenantOwner(): bool
    {
        return $this->type->isTenantOwner();
    }

    public function isCustomer(): bool
    {
        return $this->type->isCustomer();
    }
}
