<?php

namespace Src\Tenant\Domain\Entities;

use Src\Shared\Domain\Contracts\UuidGenerator;
use Src\Shared\Domain\ValueObjects\CreatedAt;
use Src\Shared\Domain\ValueObjects\SoftDeleteAt;
use Src\Shared\Domain\ValueObjects\UpdatedAt;
use Src\Shared\Domain\ValueObjects\UserEmail;
use Src\Shared\Domain\ValueObjects\Uuid;
use Src\Tenant\Domain\ValueObjects\AvatarUrl;
use Src\Tenant\Domain\ValueObjects\EmailVerifiedAt;
use Src\Tenant\Domain\ValueObjects\Password;
use Src\Tenant\Domain\ValueObjects\PhoneNumber;
use Src\Tenant\Domain\ValueObjects\PinVerification;
use Src\Tenant\Domain\ValueObjects\UserName;
use Src\Tenant\Domain\ValueObjects\UserStatus;
use Src\Tenant\Domain\ValueObjects\UserType;

class TenantOwner
{
    private ?Uuid $id;

    private UserName $name;

    private UserEmail $email;

    private ?Password $password;

    private ?EmailVerifiedAt $emailVerifiedAt;

    private ?PinVerification $pin;

    private UserType $type;

    private ?PhoneNumber $phone;

    private ?AvatarUrl $avatar;

    private UserStatus $isActive;

    private ?CreatedAt $createdAt;

    private ?UpdatedAt $updatedAt;

    private ?SoftDeleteAt $softdeleteAt;

    private function __construct(
        ?Uuid $id,
        UserName $name,
        UserEmail $email,
        ?Password $password,
        ?EmailVerifiedAt $emailVerifiedAt,
        ?PinVerification $pin,
        UserType $type,
        ?PhoneNumber $phone,
        ?AvatarUrl $avatar,
        UserStatus $isActive,
        ?CreatedAt $createdAt = null,
        ?UpdatedAt $updatedAt = null,
        ?SoftDeleteAt $softdeleteAt = null,
    ) {
        $this->id = $id;
        $this->name = $name;
        $this->email = $email;
        $this->password = $password;
        $this->emailVerifiedAt = $emailVerifiedAt;
        $this->pin = $pin;
        $this->type = $type;
        $this->phone = $phone;
        $this->avatar = $avatar;
        $this->isActive = $isActive;
        $this->createdAt = $createdAt;
        $this->updatedAt = $updatedAt;
        $this->softdeleteAt = $softdeleteAt;
    }

    // Factory method - genera su propio ID
    public static function create(
        UuidGenerator $generator,
        UserName $name,
        UserEmail $email,
        ?Password $password,
        ?EmailVerifiedAt $emailVerifiedAt,
        ?PinVerification $pin,
        UserType $type,
        ?PhoneNumber $phone,
        ?AvatarUrl $avatar,
        UserStatus $isActive,
    ): self {
        return new self(
            Uuid::generate($generator),  // ← Auto-generado
            $name,
            $email,
            $password,
            $emailVerifiedAt,
            $pin,
            $type,
            $phone,
            $avatar,
            $isActive,
            CreatedAt::now(),
            UpdatedAt::now(),
            null,
        );
    }

    // Factory method - para reconstruir desde BD
    public static function reconstitute(
        Uuid $id,
        UserName $name,
        UserEmail $email,
        ?Password $password,
        ?EmailVerifiedAt $emailVerifiedAt,
        ?PinVerification $pin,
        UserType $type,
        ?PhoneNumber $phone,
        ?AvatarUrl $avatar,
        UserStatus $isActive,
        CreatedAt $createdAt,
        UpdatedAt $updatedAt,
        ?SoftDeleteAt $softDeleteAt
    ): self {
        // return new self($id, $email, $createdAt);
        return new self(
            $id,
            $name,
            $email,
            $password,
            $emailVerifiedAt,
            $pin,
            $type,
            $phone,
            $avatar,
            $isActive,
            $createdAt,
            $updatedAt,
            $softDeleteAt
        );
    }

    public function getId(): Uuid
    {
        return $this->id;
    }

    public function getName(): UserName
    {
        return $this->name;
    }

    public function getPassword(): ?Password
    {
        return $this->password;
    }

    public function getEmail(): UserEmail
    {
        return $this->email;
    }

    public function getType(): UserType
    {
        return $this->type;
    }

    public function getPhone(): ?PhoneNumber
    {
        return $this->phone;
    }

    public function getAvatar(): ?AvatarUrl
    {
        return $this->avatar;
    }

    public function getCreatedAt(): ?CreatedAt
    {
        return $this->createdAt;
    }

    public function getUpdatedAt(): ?UpdatedAt
    {
        return $this->updatedAt;
    }

    public function getSoftDeleteAt(): ?SoftDeleteAt
    {
        return $this->softDeleteAt;
    }

    public function isActive(): bool
    {
        return $this->isActive->isActive();
    }

    public function hasPhone(): bool
    {
        return $this->phone !== null;
    }

    public function hasAvatar(): bool
    {
        return $this->avatar !== null && ! $this->avatar->isDefault();
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

    public function canManageUsers(): bool
    {
        return $this->type->canManageUsers();
    }

    public function activate(): void
    {
        $this->isActive = UserStatus::active();
        $this->updatedAt = UpdatedAt::now();
    }

    public function deactivate(): void
    {
        $this->isActive = UserStatus::inactive();
        $this->updatedAt = UpdatedAt::now();
    }

    public function updatePersonalData(UserName $name, PhoneNumber $phone)
    {
        $this->name = $name;
        $this->phone = $phone;
        $this->updatedAt = $this->updatedAt->now();
    }

    public function setPassword(Password $password): void
    {
        $this->password = $password;
    }
}
