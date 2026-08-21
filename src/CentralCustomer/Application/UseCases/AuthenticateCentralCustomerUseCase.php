<?php

declare(strict_types=1);

namespace Src\CentralCustomer\Application\UseCases;

use Src\CentralCustomer\Infrastructure\Eloquent\Models\CentralCustomer;
use Exception;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

final class AuthenticateCentralCustomerUseCase
{
    /**
     * @param string $email
     * @param string $password
     * @return array{customer: CentralCustomer, token: string}
     */
    public function execute(string $email, string $password): array
    {
        $customer = CentralCustomer::where('email', strtolower(trim($email)))->first();

        if (! $customer || ! Hash::check($password, $customer->password)) {
            throw new Exception('Credenciales inválidas. Verifica tu correo y contraseña.', 401);
        }

        if (! $customer->is_active) {
            throw new Exception('Tu cuenta se encuentra suspendida o inactiva.', 403);
        }

        return [
            'customer' => $customer->load('addresses'),
            'token' => (string) Str::random(64),
        ];
    }
}
