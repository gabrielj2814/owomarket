<?php

declare(strict_types=1);

namespace Src\ExchangeRate\Infrastructure\Notifications;

use App\Mail\StaleExchangeRateMail;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Src\ExchangeRate\Domain\Contracts\StaleRateAlerter;
use Src\ExchangeRate\Domain\Entities\ExchangeRate;
use Src\Shared\Domain\ValueObjects\UserType;
use Src\User\Infrastructure\Eloquent\Models\User;
use Throwable;

/**
 * Envía el aviso de tasa congelada por correo a los superadministradores (hallazgo N20).
 *
 * **Una vez al día como mucho.** `exchange-rate:sync-bcv` corre tres veces cada día
 * laborable, así que sin freno un BCV caído una semana produciría 15 correos y el aviso
 * dejaría de leerse justo cuando importa. La marca va en caché con caducidad al final
 * del día para que el recordatorio vuelva mañana si el problema sigue abierto.
 *
 * **Un fallo de correo no puede tumbar la sincronización.** Si el mailer no responde, se
 * registra y se sigue: el `error` del log (hallazgo D4) continúa siendo la red de
 * seguridad, y devolver la tasa de respaldo al sitio importa más que el aviso.
 */
final class MailStaleRateAlerter implements StaleRateAlerter
{
    private const CACHE_KEY = 'exchange_rate:stale_alert_sent';

    public function alertStaleRate(ExchangeRate $activeRate, int $daysStale, string $errorMessage): void
    {
        if (! Cache::add(self::CACHE_KEY, true, now()->endOfDay())) {
            return;
        }

        $destinatarios = User::query()
            ->where('type', UserType::SUPER_ADMIN)
            ->where(fn ($q) => $q->where('is_active', true)->orWhereNull('is_active'))
            ->get(['name', 'email']);

        if ($destinatarios->isEmpty()) {
            Log::warning('Tasa BCV congelada, pero no hay ningún superadministrador activo a quien avisar.', [
                'days_stale' => $daysStale,
            ]);

            return;
        }

        foreach ($destinatarios as $destinatario) {
            try {
                Mail::to($destinatario->email)->send(new StaleExchangeRateMail(
                    recipientName: (string) $destinatario->name,
                    activeRate: $activeRate->getRate()->value(),
                    rateDate: $activeRate->getRateDate()->value(),
                    daysStale: $daysStale,
                    errorMessage: $errorMessage
                ));
            } catch (Throwable $e) {
                Log::error('No se pudo enviar el aviso de tasa BCV congelada.', [
                    'email' => $destinatario->email,
                    'exception' => $e->getMessage(),
                ]);
            }
        }
    }
}
