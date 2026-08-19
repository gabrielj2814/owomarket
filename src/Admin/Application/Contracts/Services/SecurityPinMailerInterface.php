<?php

namespace Src\Admin\Application\Contracts\Services;

use Src\Admin\Domain\ValueObjects\PinVerification;
use Src\Admin\Domain\ValueObjects\UserName;
use Src\Shared\Domain\ValueObjects\UserEmail;

interface SecurityPinMailerInterface
{
    /**
     * Enviar correo electrónico con el PIN de seguridad de 6 dígitos.
     */
    public function sendPinMail(UserEmail $email, UserName $name, PinVerification $pin): void;
}
