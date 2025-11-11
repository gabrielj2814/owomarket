<?php


namespace Src\User\Domain\ValueObjects;


class UserEmail
{
    //
    private string $email;

    public function __construct(string $email)
    {

        if(filter_var($email, FILTER_VALIDATE_EMAIL) === false){
            throw new \InvalidArgumentException("Correo invalido: " . $email);
        }

        $this->email = $email;
    }

    public function value(): string
    {
        return $this->email;
    }
}



?>
