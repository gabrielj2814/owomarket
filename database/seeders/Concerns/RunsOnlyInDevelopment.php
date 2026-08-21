<?php

declare(strict_types=1);

namespace Database\Seeders\Concerns;

/**
 * Hallazgo F6: los seeders de demostración se pueden invocar sueltos
 * (`db:seed --class=...`, o el comando `tenants:seed-domains` de `routes/console.php`),
 * así que la guarda de `DatabaseSeeder` no basta: cada uno tiene que negarse por su cuenta
 * a crear usuarios con contraseñas conocidas y datos de mentira fuera de local y testing.
 */
trait RunsOnlyInDevelopment
{
    protected function shouldSkipOutsideDevelopment(): bool
    {
        if (app()->environment(['local', 'testing'])) {
            return false;
        }

        $this->command?->warn(
            static::class.' omitido: los seeders de demostración sólo se ejecutan en los entornos local y testing.'
        );

        return true;
    }
}
