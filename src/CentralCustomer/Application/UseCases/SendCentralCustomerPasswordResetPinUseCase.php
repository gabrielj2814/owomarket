<?php

declare(strict_types=1);

namespace Src\CentralCustomer\Application\UseCases;

use Illuminate\Support\Str;
use Src\CentralCustomer\Infrastructure\Eloquent\Models\CentralCustomer;
use Src\CentralCustomer\Infrastructure\Eloquent\Models\CentralCustomerPasswordReset;

final class SendCentralCustomerPasswordResetPinUseCase
{
    /**
     * Mensaje unico para las dos salidas (hallazgo A3).
     *
     * Antes esto respondia 200 «Se ha generado el codigo» cuando la cuenta existia y 404
     * «No existe una cuenta registrada con este correo» cuando no, y la pagina de
     * recuperacion mostraba ese texto tal cual. Distinguian en codigo HTTP *y* en texto,
     * asi que cualquiera podia pasar una lista de correos y quedarse con los que tienen
     * cuenta aqui — sin medir tiempos ni afinar nada. Combinado con A2 (el PIN sin limite
     * de intentos) eso era la mitad del trabajo hecha: primero que cuentas hay, despues
     * el PIN de cada una.
     *
     * Al usuario legitimo no le cuesta nada: va a ir a su correo de todas formas.
     */
    private const MENSAJE_NEUTRO = 'Si ese correo tiene una cuenta, te hemos enviado un código de recuperación.';

    /**
     * @return array{success: bool, message: string, email: string, pin_code?: string, expires_at: string}
     */
    public function execute(string $email): array
    {
        $normalizedEmail = strtolower(trim($email));

        $customer = CentralCustomer::where('email', $normalizedEmail)->first();
        if (! $customer) {
            // Salida silenciosa: misma forma, mismo mensaje, mismo 200. No se crea
            // ningun registro y no se envia ningun correo.
            //
            // Queda una diferencia de tiempo — la rama de arriba escribe en la base y
            // esta no— que en teoria sigue distinguiendo las dos. Es una senal mucho mas
            // debil que un 404 con texto explicito, y taparla del todo pide trabajo
            // simulado. Se deja anotado como lo que es: reducido, no eliminado.
            return [
                'success' => true,
                'message' => self::MENSAJE_NEUTRO,
                'email' => $normalizedEmail,
                'expires_at' => now()->addMinutes(15)->toIso8601String(),
            ];
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
            'message' => self::MENSAJE_NEUTRO,
            'email' => $normalizedEmail,
            'pin_code' => $pinCode,
            'expires_at' => $expiresAt->toIso8601String(),
        ];
    }
}
