<?php

namespace Src\User\Domain\Entities;

use DateTime;
use Src\User\Domain\ValueObjects\UserEmail;
use Src\User\Domain\ValueObjects\Uuid;

class User {
    private ?Uuid $id;
    private UserEmail $email;
    // private DateTime $createdAt;

    // Constructor privado
    private function __construct(?Uuid $id, UserEmail $email) {
        $this->id = $id;
        $this->email = $email;
        // $this->createdAt = $createdAt;
    }

    // Factory method - genera su propio ID
    public static function create(UserEmail $email): self {
        return new self(
            Uuid::generate(),  // ← Auto-generado
            $email,
            // new DateTime()
        );
    }

    // Factory method - para reconstruir desde BD
    public static function reconstitute(?Uuid $id, UserEmail $email): self {
        // return new self($id, $email, $createdAt);
        return new self($id, $email);
    }

    public function getId(): Uuid {
        return $this->id;
    }

    public function getEmail(): UserEmail {
        return $this->email;
    }





}


?>
