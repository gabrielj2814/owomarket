<?php

namespace Src\User\Domain\Entities;

use DateTime;
use Src\User\Domain\ValueObjects\Password;
use Src\User\Domain\ValueObjects\UserEmail;
use Src\User\Domain\ValueObjects\UserName;
use Src\User\Domain\ValueObjects\Uuid;

class User {
    private ?Uuid $id;
    private UserName $name;
    private UserEmail $email;
    private Password $password;
    // private DateTime $createdAt;

    // Constructor privado
    private function __construct(?Uuid $id,UserName $name, UserEmail $email, Password $password) {
        $this->id = $id;
        $this->name = $name;
        $this->email = $email;
        $this->password = $password;
        // $this->createdAt = $createdAt;
    }

    // Factory method - genera su propio ID
    public static function create(UserName $name,UserEmail $email,Password $password): self {
        return new self(
            Uuid::generate(),  // ← Auto-generado
            $name,
            $email,
            $password,
            // new DateTime()
        );
    }

    // Factory method - para reconstruir desde BD
    public static function reconstitute(?Uuid $id, UserName $name, UserEmail $email, Password $password): self {
        // return new self($id, $email, $createdAt);
        return new self($id, $name, $email, $password);
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





}


?>
