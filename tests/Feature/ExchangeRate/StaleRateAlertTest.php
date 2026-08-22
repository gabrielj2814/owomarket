<?php

declare(strict_types=1);

use App\Mail\StaleExchangeRateMail;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use Src\ExchangeRate\Application\UseCase\SyncBcvExchangeRateUseCase;
use Src\ExchangeRate\Domain\Contracts\BcvScraperInterface;
use Src\ExchangeRate\Domain\Contracts\ExchangeRateRepositoryInterface;
use Src\ExchangeRate\Domain\Contracts\StaleRateAlerter;
use Src\ExchangeRate\Domain\Entities\ExchangeRate;
use Src\ExchangeRate\Domain\ValueObjects\CurrencyCode;
use Src\ExchangeRate\Domain\ValueObjects\RateAmount;
use Src\ExchangeRate\Domain\ValueObjects\RateDate;
use Src\ExchangeRate\Domain\ValueObjects\RateSource;
use Src\ExchangeRate\Infrastructure\Notifications\MailStaleRateAlerter;
use Src\Shared\Infrastructure\Security\LaravelUuidGenerator;
use Src\User\Infrastructure\Eloquent\Models\User;

/*
| Hallazgo N20: el `error` que registraba el fallback prolongado del BCV no llegaba a
| nadie. Estos tests fijan las tres cosas que importan: que el aviso salga cuando la tasa
| lleva días congelada, que NO salga por un fallo puntual, y que no se repita el mismo día.
*/

/** Construye el caso de uso con un scraper que siempre falla y una tasa activa de $daysStale días. */
function useCaseConTasaCongelada(int $daysStale, ?StaleRateAlerter $alerter): SyncBcvExchangeRateUseCase
{
    $generator = new LaravelUuidGenerator;

    $scraper = Mockery::mock(BcvScraperInterface::class);
    $scraper->shouldReceive('fetchUsdRate')->andReturn([
        'rate' => 0.0,
        'rate_date' => now()->toDateString(),
        'raw_html' => null,
        'success' => false,
        'error_message' => 'Timeout contra bcv.org.ve',
    ]);

    $congelada = ExchangeRate::create(
        $generator,
        CurrencyCode::usd(),
        CurrencyCode::ves(),
        RateAmount::make(750.0),
        RateSource::bcv(),
        RateDate::make(now()->subDays($daysStale)->toDateString())
    );

    $repo = Mockery::mock(ExchangeRateRepositoryInterface::class);
    $repo->shouldReceive('findActive')->andReturn($congelada);

    return new SyncBcvExchangeRateUseCase($scraper, $repo, $generator, null, $alerter);
}

function crearSuperAdmin(string $email): User
{
    return User::forceCreate([
        'id' => (string) Str::uuid(),
        'name' => 'Super Admin',
        'email' => $email,
        'password' => bcrypt('EndAdmin_12345678'),
        'type' => 'super_admin',
    ]);
}

test('avisa al superadministrador cuando la tasa lleva días congelada', function () {
    Mail::fake();
    crearSuperAdmin('super@owomarket.local');

    useCaseConTasaCongelada(5, new MailStaleRateAlerter)->execute();

    Mail::assertSent(StaleExchangeRateMail::class, function ($mail) {
        return $mail->hasTo('super@owomarket.local')
            && $mail->daysStale === 5
            && $mail->activeRate === 750.0;
    });
});

test('un fallo puntual no dispara el aviso', function () {
    Mail::fake();
    crearSuperAdmin('super@owomarket.local');

    // Por debajo de STALE_RATE_ALERT_DAYS: es un incidente, no una emergencia.
    useCaseConTasaCongelada(1, new MailStaleRateAlerter)->execute();

    Mail::assertNothingSent();
});

test('no repite el aviso el mismo día', function () {
    Mail::fake();
    crearSuperAdmin('super@owomarket.local');

    // `exchange-rate:sync-bcv` corre tres veces al día laborable. Sin freno, un BCV caído
    // una semana produciría 15 correos y el aviso dejaría de leerse.
    useCaseConTasaCongelada(5, new MailStaleRateAlerter)->execute();
    useCaseConTasaCongelada(5, new MailStaleRateAlerter)->execute();
    useCaseConTasaCongelada(5, new MailStaleRateAlerter)->execute();

    Mail::assertSent(StaleExchangeRateMail::class, 1);
});

test('sin superadministradores no revienta la sincronización', function () {
    Mail::fake();
    Cache::flush();

    $resultado = useCaseConTasaCongelada(5, new MailStaleRateAlerter)->execute();

    // Lo que importa: el sitio sigue recibiendo la tasa de respaldo.
    expect($resultado->getRate()->value())->toBe(750.0);
    Mail::assertNothingSent();
});
