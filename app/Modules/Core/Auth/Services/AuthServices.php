<?php


namespace App\Modules\Core\Auth\Services;

use App\Modules\Core\Auth\Contracts\Auth;
use App\Modules\Core\Auth\Data\AurhCredencialesData;
use App\Modules\Core\Shared\VOs\UserEmail;
use App\Modules\Core\User\Repositories\PersonalAccessTokenRepository;
use App\Modules\Core\User\Repositories\UserRepository;
use Illuminate\Support\Facades\Auth as FacadesAuth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;

class AuthServices implements Auth {

    public function __construct(
        protected UserRepository $userRepository,
        protected PersonalAccessTokenRepository $personalAccessTokenRepository
    )
    {}


    public function login(AurhCredencialesData $credentials): bool
    {
        $userMail=new UserEmail($credentials->email);
        $user=$this->userRepository->consultarPorMail($userMail);
         if(!$user){
            return false;
        }

        if(!Hash::check($credentials->password,$user->password)){
            return false;
        }

        FacadesAuth::login($user);

        return true;
    }

    public function loginApi(AurhCredencialesData $credentials): ?string
    {
        $userMail=new UserEmail($credentials->email);
        $user=$this->userRepository->consultarPorMail($userMail);
        Log::info("User fetched: ", ['user' => $user]);
         if(!$user){
            return false;
        }

        if(!Hash::check($credentials->password,$user->password)){
            return false;
        }

        $token=$this->personalAccessTokenRepository->generarToken($user);

        return $token;
    }

    public function logout(): void
    {
        FacadesAuth::logout();
    }

    public function logoutApi(string $token): void
    {
        $this->personalAccessTokenRepository->deleteToken($token);
    }



}




?>
