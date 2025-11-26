<?php


namespace Src\Authentication\Infrastructure\Eloquent\Repositories;

use Illuminate\Support\Facades\Auth;
use Src\Authentication\Application\Contracts\Repositories\LoginWebRepositoryInterface;
use Src\Authentication\Domain\ValueObjects\UserEmail;
use Src\Authentication\Infrastructure\Eloquent\Models\User;

class LoginWebRepository implements LoginWebRepositoryInterface {

    public function loginWebUser(UserEmail $email): void{
        $user= User::where('email', $email->value())->first();
        Auth::login($user);
    }

    public function logoutWebUser(): void{
        Auth::logout();
    }



}


?>
