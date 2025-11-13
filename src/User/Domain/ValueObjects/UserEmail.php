<?php


namespace Src\User\Domain\ValueObjects;


final class UserEmail
{
    //
    private string $email;

    private function __construct(string $email)
    {
        $this->email = $email;
    }

    private static function validate(string $email){
         if(filter_var($email, FILTER_VALIDATE_EMAIL) === false){
            throw new \InvalidArgumentException("Correo invalido: " . $email);
        }
    }

    public static function make(string $email):self{
        self::validate($email);

        return new self($email);
    }

    public function value(): string
    {
        return $this->email;
    }
}



?>
