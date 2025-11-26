<?php

namespace Src\Authentication\Domain\Entities;

use Src\Authentication\Domain\ValueObjects\Password;
use Src\Authentication\Domain\ValueObjects\UserEmail;
use Src\Authentication\Domain\ValueObjects\UserName;
use Src\Authentication\Domain\ValueObjects\UserStatus;
use Src\Authentication\Domain\ValueObjects\UserType;
use Src\Authentication\Domain\ValueObjects\Uuid;

class User {
    private ?Uuid               $id;
    private UserName            $name;
    private UserEmail           $email;
    private Password            $password;
    private UserType            $type;
    private UserStatus          $isActive;
    // Constructor privado
    private function __construct(
        ?Uuid               $id,
        UserName            $name,
        UserEmail           $email,
        Password            $password,
        UserType            $type,
        UserStatus          $isActive,
        ) {
        $this->id                = $id;
        $this->name              = $name;
        $this->email             = $email;
        $this->password          = $password;
        $this->type              = $type;
        $this->isActive          = $isActive;
    }

    // Factory method - genera su propio ID
    public static function create(
        UserName            $name,
        UserEmail           $email,
        Password            $password,
        UserType            $type,
        UserStatus          $isActive,
        ): self {
        return new self(
            Uuid::generate(),  // ← Auto-generado
            $name,
            $email,
            $password,
            $type,
            $isActive,
        );
    }

    // Factory method - para reconstruir desde BD
    public static function reconstitute(
        ?Uuid               $id,
        UserName            $name,
        UserEmail           $email,
        Password            $password,
        UserType            $type,
        UserStatus          $isActive,
        ): self {
        // return new self($id, $email, $createdAt);
        return new self(
            $id,
            $name,
            $email,
            $password,
            $type,
            $isActive,
        );
    }

    public function getId(): Uuid {
        return $this->id;
    }

    public function getName(): UserName {
        return $this->name;
    }

    public function getEmail(): UserEmail {
        return $this->email;
    }

    public function getPassword(): Password {
        return $this->password;
    }

    public function getType(): UserType {
        return $this->type;
    }

    public function isActive(): bool {
        return $this->isActive->isActive();
    }

    public function canLogin(): bool {
        return $this->isActive->canLogin();
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




}


?>
