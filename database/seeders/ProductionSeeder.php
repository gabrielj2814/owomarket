<?php

declare(strict_types=1);

namespace Database\Seeders;

use Illuminate\Database\Seeder;

/**
 * Datos maestros del sistema: reales, idempotentes y seguros de ejecutar en producción.
 *
 * Hallazgo F6: `DatabaseSeeder` mezclaba estos datos con los de demostración, así que no
 * había forma de cargar el catálogo maestro o la tasa de cambio inicial sin crear de paso
 * el superadmin con contraseña conocida y las ocho tiendas de mentira.
 *
 * Aquí sólo entra lo que un despliegue real necesita. Ante la duda, va en `DatabaseSeeder`.
 */
class ProductionSeeder extends Seeder
{
    public function run(): void
    {
        $this->call([
            // Marcas y categorías maestras del marketplace central.
            CentralMasterCatalogSeeder::class,
            // Tasa USD -> VES inicial. Desde la Fase 1.4 su ausencia deja
            // `/api/exchange-rate/convert` devolviendo 404, así que no es opcional.
            ExchangeRateSeeder::class,
        ]);
    }
}
