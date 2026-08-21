<?php

declare(strict_types=1);

use Database\Seeders\ProductionSeeder;
use Database\Seeders\RootUserSeeder;
use Illuminate\Support\Facades\DB;
use Src\ExchangeRate\Domain\Contracts\ExchangeRateRepositoryInterface;
use Src\ExchangeRate\Domain\ValueObjects\CurrencyCode;

/**
 * Hallazgo F6: `DatabaseSeeder` invocaba los seeders de demostración sin ninguna guarda,
 * así que un `php artisan db:seed --force` en producción creaba el superadmin
 * `root@owomarket.local` con la contraseña de desarrollo — y se la reseteaba si ya existía.
 */
test('RootUserSeeder refuses to create the superadmin outside local and testing', function () {
    app()->detectEnvironment(fn () => 'production');

    // Invocado directamente y no con `$this->seed()`, porque `db:seed` pide confirmación
    // interactiva en producción antes siquiera de llegar al seeder.
    app(RootUserSeeder::class)->setContainer(app())->run();

    expect(DB::table('users')->where('email', 'root@owomarket.local')->exists())->toBeFalse();
});

test('RootUserSeeder still creates the superadmin in testing', function () {
    app(RootUserSeeder::class)->setContainer(app())->run();

    expect(DB::table('users')->where('email', 'root@owomarket.local')->exists())->toBeTrue();
});

// Los datos maestros sí deben cargarse en producción: son reales, no de mentira.
test('ProductionSeeder loads master data outside local and testing', function () {
    app()->detectEnvironment(fn () => 'production');

    app(ProductionSeeder::class)->setContainer(app())->run();

    $activeRate = app(ExchangeRateRepositoryInterface::class)
        ->findActive(CurrencyCode::usd(), CurrencyCode::ves());

    expect($activeRate)->not->toBeNull()
        ->and(DB::table('central_brands')->count())->toBeGreaterThan(0);
});
