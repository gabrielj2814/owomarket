<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // Datos maestros: reales y necesarios también en producción.
        $this->call(ProductionSeeder::class);

        // Hallazgo F6: los seeders de demostración no estaban condicionados al entorno.
        // Un `php artisan db:seed --force` en producción creaba el superadmin con una
        // contraseña conocida, ocho dueños de tienda de mentira y el catálogo de prueba.
        if (! app()->environment(['local', 'testing'])) {
            $this->command?->warn(
                'Seeders de demostración omitidos: sólo se ejecutan en los entornos local y testing.'
            );

            return;
        }

        $this->call([
            RootUserSeeder::class,
            TenantDomainSeeder::class,
            TenantDefaultUsersSeeder::class,
            TenantDemoDataSeeder::class,
            CentralCustomerDemoSeeder::class,
        ]);
    }
}
