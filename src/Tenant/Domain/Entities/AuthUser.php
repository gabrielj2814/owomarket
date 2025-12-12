<?php


namespace Src\Tenant\Domain\Entities;

use Src\Tenant\Domain\ValuesObjects\AvatarUrl;
use Src\Tenant\Domain\ValuesObjects\UserEmail;
use Src\Tenant\Domain\ValuesObjects\UserName;
use Src\Tenant\Domain\ValuesObjects\UserType;
use Src\Tenant\Domain\ValuesObjects\Uuid;

class AuthUser {

    private Uuid                $user_id;
    private UserName            $name;
    private UserEmail           $email;
    private UserType            $type;
    private ?AvatarUrl          $avatar;


    // Constructor privado
    private function __construct(
        ?Uuid               $user_id,
        UserName            $name,
        UserEmail           $email,
        UserType            $type,
        ?AvatarUrl          $avatar,
        ) {
        $this->user_id           = $user_id;
        $this->name              = $name;
        $this->email             = $email;
        $this->type              = $type;
        $this->avatar            = $avatar;
    }

    // Factory method - genera su propio ID
    public static function create(
        Uuid                $user_id,
        UserName            $name,
        UserEmail           $email,
        UserType            $type,
        ?AvatarUrl          $avatar,
        ): self {
        return new self(
            $user_id,
            $name,
            $email,
            $type,
            $avatar,
        );
    }

    // Factory method - para reconstruir desde BD
    public static function reconstitute(
        Uuid                $user_id,
        UserName            $name,
        UserEmail           $email,
        UserType            $type,
        ?AvatarUrl          $avatar,
        ): self {
        // return new self($id, $email, $createdAt);
        return new self(
            $user_id,
            $name,
            $email,
            $type,
            $avatar
        );
    }

    public function getUserId(): Uuid {
        return $this->user_id;
    }

    public function getName(): UserName {
        return $this->name;
    }

    public function getEmail(): UserEmail {
        return $this->email;
    }

    public function getType(): UserType {
        return $this->type;
    }

    public function getAvatar(): ?AvatarUrl {
        return $this->avatar;
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

}


?>
