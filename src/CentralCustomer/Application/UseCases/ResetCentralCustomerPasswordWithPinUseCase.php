<?php

declare(strict_types=1);

namespace Src\CentralCustomer\Application\UseCases;

use Src\CentralCustomer\Infrastructure\Eloquent\Models\CentralCustomer;
use Src\CentralCustomer\Infrastructure\Eloquent\Models\CentralCustomerPasswordReset;
use Exception;
use Illuminate\Support\Facades\Hash;

final class ResetCentralCustomerPasswordWithPinUseCase
{
    /**
     * @return array{success: bool, message: string}
     */
    public function execute(string $email, string $pinCode, string $newPassword): array
    {
        $normalizedEmail = strtolower(trim($email));
        $cleanPin = trim($pinCode);

        $resetRecord = CentralCustomerPasswordReset::where('email', $normalizedEmail)
            ->where('pin_code', $cleanPin)
            ->where('expires_at', '>', now())
            ->first();

        if (! $resetRecord) {
            throw new Exception('El código de seguridad es inválido o ha expirado. Por favor solicita uno nuevo.', 422);
        }

        $customer = CentralCustomer::where('email', $normalizedEmail)->first();
        if (! $customer) {
            throw new Exception('No se encontró la cuenta de usuario especificada.', 404);
        }

        $customer->update([
            'password' => Hash::make($newPassword),
        ]);

        // Limpiar tokens utilizados
        CentralCustomerPasswordReset::where('email', $normalizedEmail)->delete();

        return [
            'success' => true,
            'message' => 'Contraseña restablecida exitosamente. Ya puedes iniciar sesión con tus nuevas credenciales.',
        ];
    }
}
