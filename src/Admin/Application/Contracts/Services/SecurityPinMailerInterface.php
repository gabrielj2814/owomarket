<?php

namespace Src\Admin\Application\Contracts\Services;

use Src\Shared\Domain\ValueObjects\PinVerification;
use Src\Shared\Domain\ValueObjects\UserEmail;
use Src\Shared\Domain\ValueObjects\UserName;

interface SecurityPinMailerInterface
{
    /**
     * Enviar correo electrónico con el PIN de seguridad de 6 dígitos.
     */
    public function sendPinMail(UserEmail $email, UserName $name, PinVerification $pin): void;
}
