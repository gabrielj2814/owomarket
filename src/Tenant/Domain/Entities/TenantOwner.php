<?php

namespace Src\Tenant\Domain\Entities;

use Src\Shared\ValuesObjects\CreatedAt;
use Src\Shared\ValuesObjects\UpdatedAt;
use Src\Tenant\Domain\ValuesObjects\AvatarUrl;
use Src\Tenant\Domain\ValuesObjects\EmailVerifiedAt;
use Src\Tenant\Domain\ValuesObjects\Password;
use Src\Tenant\Domain\ValuesObjects\PhoneNumber;
use Src\Tenant\Domain\ValuesObjects\PinVerification;
use Src\Tenant\Domain\ValuesObjects\UserEmail;
use Src\Tenant\Domain\ValuesObjects\UserName;
use Src\Tenant\Domain\ValuesObjects\UserStatus;
use Src\Tenant\Domain\ValuesObjects\UserType;
use Src\Tenant\Domain\ValuesObjects\Uuid;

class TenantOwner {
    private ?Uuid               $id;
    private UserName            $name;
    private UserEmail           $email;
    private ?Password           $password;
    private ?EmailVerifiedAt    $emailVerifiedAt;
    private ?PinVerification    $pin;
    private UserType            $type;
    private ?PhoneNumber        $phone;
    private ?AvatarUrl          $avatar;
    private UserStatus          $isActive;
    private ?CreatedAt          $createdAt;
    private ?UpdatedAt          $updatedAt;
    // Constructor privado
    private function __construct(
        ?Uuid               $id,
        UserName            $name,
        UserEmail           $email,
        ?Password           $password,
        ?EmailVerifiedAt    $emailVerifiedAt,
        ?PinVerification    $pin,
        UserType            $type,
        ?PhoneNumber        $phone,
        ?AvatarUrl          $avatar,
        UserStatus          $isActive,
        ?CreatedAt          $createdAt,
        ?UpdatedAt          $updatedAt,
        ) {
        $this->id                = $id;
        $this->name              = $name;
        $this->email             = $email;
        $this->password          = $password;
        $this->emailVerifiedAt   = $emailVerifiedAt;
        $this->pin               = $pin;
        $this->type              = $type;
        $this->phone             = $phone;
        $this->avatar            = $avatar;
        $this->isActive          = $isActive;
        $this->createdAt         = $createdAt;
        $this->updatedAt         = $updatedAt;
    }

    // Factory method - genera su propio ID
    public static function create(
        UserName            $name,
        UserEmail           $email,
        ?Password           $password,
        ?EmailVerifiedAt    $emailVerifiedAt,
        ?PinVerification    $pin,
        UserType            $type,
        ?PhoneNumber        $phone,
        ?AvatarUrl          $avatar,
        UserStatus          $isActive,
        ): self {
        return new self(
            Uuid::generate(),  // ← Auto-generado
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
        );
    }

    // Factory method - para reconstruir desde BD
    public static function reconstitute(
        Uuid                $id,
        UserName            $name,
        UserEmail           $email,
        ?Password           $password,
        ?EmailVerifiedAt    $emailVerifiedAt,
        ?PinVerification    $pin,
        UserType            $type,
        ?PhoneNumber        $phone,
        ?AvatarUrl          $avatar,
        UserStatus          $isActive,
        CreatedAt           $createdAt,
        UpdatedAt           $updatedAt,
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
        );
    }

    public function getId(): Uuid {
        return $this->id;
    }

    public function getName(): UserName {
        return $this->name;
    }

    public function getPassword(): ?Password {
        return $this->password;
    }

    public function getEmail(): UserEmail {
        return $this->email;
    }

    public function getType(): UserType {
        return $this->type;
    }

    public function getPhone(): ?PhoneNumber {
        return $this->phone;
    }

    public function getAvatar(): ?AvatarUrl {
        return $this->avatar;
    }

    public function getCreatedAt(): ?CreatedAt {
        return $this->createdAt;
    }

    public function getUpdatedAt(): ?UpdatedAt {
        return $this->updatedAt;
    }


    public function isActive(): bool {
        return $this->isActive->isActive();
    }

    public function hasPhone(): bool {
        return $this->phone !== null;
    }

    public function hasAvatar(): bool {
        return $this->avatar !== null && !$this->avatar->isDefault();
    }


    public function isSuperAdmin(): bool {
        return $this->type->isSuperAdmin();
    }

    public function isTenantOwner(): bool {
        return $this->type->isTenantOwner();
    }

    public function isCustomer(): bool {
        return $this->type->isCustomer();
    }

    public function canManageUsers(): bool {
        return $this->type->canManageUsers();
    }



    public function activate(): void {
        $this->isActive = UserStatus::active();
        $this->updatedAt = UpdatedAt::now();
    }

    public function deactivate(): void {
        $this->isActive = UserStatus::inactive();
        $this->updatedAt = UpdatedAt::now();
    }




}


?>
