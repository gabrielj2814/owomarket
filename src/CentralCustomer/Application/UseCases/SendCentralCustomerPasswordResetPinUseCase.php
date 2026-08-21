<?php

declare(strict_types=1);

namespace Src\CentralCustomer\Application\UseCases;

use Src\CentralCustomer\Infrastructure\Eloquent\Models\CentralCustomer;
use Src\CentralCustomer\Infrastructure\Eloquent\Models\CentralCustomerPasswordReset;
use Exception;
use Illuminate\Support\Str;

final class SendCentralCustomerPasswordResetPinUseCase
{
    /**
     * @return array{success: bool, message: string, email: string, pin_code?: string, expires_at: string}
     */
    public function execute(string $email): array
    {
        $normalizedEmail = strtolower(trim($email));

        $customer = CentralCustomer::where('email', $normalizedEmail)->first();
        if (! $customer) {
            throw new Exception('No existe una cuenta registrada con este correo electrónico.', 404);
        }

        // Eliminar solicitudes anteriores no utilizadas
        CentralCustomerPasswordReset::where('email', $normalizedEmail)->delete();

        // Generar PIN de 6 dígitos y token seguro
        $pinCode = (string) random_int(100000, 999999);
        $token = (string) Str::random(64);
        $expiresAt = now()->addMinutes(15);

        CentralCustomerPasswordReset::create([
            'id' => (string) Str::uuid(),
            'email' => $normalizedEmail,
            'pin_code' => $pinCode,
            'token' => $token,
            'expires_at' => $expiresAt,
            'created_at' => now(),
        ]);

        return [
            'success' => true,
            'message' => 'Se ha generado el código de recuperación para tu cuenta.',
            'email' => $normalizedEmail,
            'pin_code' => $pinCode,
            'expires_at' => $expiresAt->toIso8601String(),
        ];
    }
}
