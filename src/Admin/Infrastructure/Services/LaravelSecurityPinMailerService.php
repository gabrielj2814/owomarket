<?php

namespace Src\Admin\Infrastructure\Services;

use App\Mail\SecurityPinMail;
use Illuminate\Support\Facades\Mail;
use Src\Admin\Application\Contracts\Services\SecurityPinMailerInterface;
use Src\Admin\Domain\ValueObjects\PinVerification;
use Src\Admin\Domain\ValueObjects\UserName;
use Src\Shared\Domain\ValueObjects\UserEmail;

class LaravelSecurityPinMailerService implements SecurityPinMailerInterface
{
    public function sendPinMail(UserEmail $email, UserName $name, PinVerification $pin): void
    {
        Mail::to($email->value())->send(new SecurityPinMail(
            $name->value(),
            $pin->value()
        ));
    }
}
