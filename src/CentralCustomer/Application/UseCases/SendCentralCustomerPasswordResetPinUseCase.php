<?php

declare(strict_types=1);

namespace Src\CentralCustomer\Application\UseCases;

use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Str;
use Src\CentralCustomer\Infrastructure\Eloquent\Models\CentralCustomer;
use Src\CentralCustomer\Infrastructure\Eloquent\Models\CentralCustomerPasswordReset;
use Src\CentralCustomer\Infrastructure\Notifications\PasswordResetPinNotification;
use Throwable;

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

    /** Lo que dura el PIN. Vive aqui para que el correo no prometa un plazo distinto al real. */
    private const MINUTOS_DE_VALIDEZ = 15;

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
                'expires_at' => now()->addMinutes(self::MINUTOS_DE_VALIDEZ)->toIso8601String(),
            ];
        }

        // Eliminar solicitudes anteriores no utilizadas
        CentralCustomerPasswordReset::where('email', $normalizedEmail)->delete();

        // Generar PIN de 6 dígitos y token seguro
        $pinCode = (string) random_int(100000, 999999);
        $token = (string) Str::random(64);
        $expiresAt = now()->addMinutes(self::MINUTOS_DE_VALIDEZ);

        CentralCustomerPasswordReset::create([
            'id' => (string) Str::uuid(),
            'email' => $normalizedEmail,
            'pin_code' => $pinCode,
            'token' => $token,
            'expires_at' => $expiresAt,
            'created_at' => now(),
        ]);

        /*
         * **El envio que faltaba.** Hasta la fase 3 de notificaciones este caso de uso generaba
         * el PIN, lo guardaba y no lo mandaba a ningun sitio: el controlador lo devuelve en la
         * respuesta solo en `local` y `testing`, asi que en produccion nadie podia recuperar su
         * contraseña.
         *
         * Va por `route('mail', ...)` y no a un modelo porque quien recupera **no tiene sesion**:
         * no hay cuenta a la que colgarselo ni campana donde pudiera verlo.
         *
         * Un fallo de correo no puede tumbar la peticion --el PIN ya esta guardado y sigue
         * siendo valido-- pero tiene que dejar rastro: sin el, un SMTP caido se veria igual que
         * un correo entregado.
         */
        try {
            Notification::route('mail', $normalizedEmail)
                ->notify(new PasswordResetPinNotification($pinCode, self::MINUTOS_DE_VALIDEZ));
        } catch (Throwable $e) {
            Log::error('No se pudo enviar el codigo de recuperacion.', [
                'email' => $normalizedEmail,
                'error' => $e->getMessage(),
            ]);
        }

        return [
            'success' => true,
            'message' => self::MENSAJE_NEUTRO,
            'email' => $normalizedEmail,
            'pin_code' => $pinCode,
            'expires_at' => $expiresAt->toIso8601String(),
        ];
    }
}
