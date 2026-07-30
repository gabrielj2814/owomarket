<?php

namespace Src\Admin\Application\Contracts\Services;

use Src\Admin\Domain\ValueObjects\PinVerification;
use Src\Admin\Domain\ValueObjects\UserEmail;
use Src\Admin\Domain\ValueObjects\UserName;

interface SecurityPinMailerInterface
{
    /**
     * Enviar correo electrónico con el PIN de seguridad de 6 dígitos.
     */
    public function sendPinMail(UserEmail $email, UserName $name, PinVerification $pin): void;
}
