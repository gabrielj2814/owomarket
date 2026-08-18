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

class AuthServices implements Auth
{
    /**
     * Constructor del servicio de autenticación.
     *
     * @param  UserRepository  $userRepository  Repositorio de usuarios.
     * @param  PersonalAccessTokenRepository  $personalAccessTokenRepository  Repositorio de tokens.
     */
    public function __construct(
        protected UserRepository $userRepository,
        protected PersonalAccessTokenRepository $personalAccessTokenRepository
    ) {}

    /**
     * Autentica a un usuario para la aplicación web.
     *
     * @param  AurhCredencialesData  $credentials  Credenciales del usuario.
     * @return bool Retorna true si la autenticación es exitosa, false en caso contrario.
     */
    public function login(AurhCredencialesData $credentials): bool
    {
        $userMail = new UserEmail($credentials->email);
        $user = $this->userRepository->consultarPorMail($userMail);
        if (! $user) {
            return false;
        }

        if (! Hash::check($credentials->password, $user->password)) {
            return false;
        }

        FacadesAuth::login($user);

        return true;
    }

    /**
     * Autentica a un usuario y devuelve un token para API.
     *
     * @param  AurhCredencialesData  $credentials  Credenciales del usuario.
     * @return string|null El token generado, o null si falla la autenticación.
     */
    public function loginApi(AurhCredencialesData $credentials): ?string
    {
        $userMail = new UserEmail($credentials->email);
        $user = $this->userRepository->consultarPorMail($userMail);
        Log::info('User fetched: ', ['user' => $user]);
        if (! $user) {
            return false;
        }

        if (! Hash::check($credentials->password, $user->password)) {
            return false;
        }

        $token = $this->personalAccessTokenRepository->generarToken($user);

        return $token;
    }

    /**
     * Cierra la sesión del usuario actual en la aplicación web.
     */
    public function logout(): void
    {
        FacadesAuth::logout();
    }

    /**
     * Invalida el token del usuario logueado en la API.
     *
     * @param  string  $token  Token de acceso a revocar.
     */
    public function logoutApi(string $token): void
    {
        $this->personalAccessTokenRepository->deleteToken($token);
    }
}
