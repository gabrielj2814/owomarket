<?php

declare(strict_types=1);

namespace Database\Seeders;

use Database\Seeders\Concerns\RunsOnlyInDevelopment;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;
use Src\Payment\Infrastructure\Eloquent\Models\CentralSetting;

/**
 * Datos de cobro de la plataforma para el checkout del marketplace central.
 *
 * Son de mentira, igual que el resto de seeders de demostracion, y por eso llevan la
 * guarda de entorno de la Fase 2.1: en produccion los tiene que cargar el superadmin con
 * los datos reales, o el metodo sencillamente no se ofrece.
 */
class CentralPaymentDemoSeeder extends Seeder
{
    use RunsOnlyInDevelopment;

    public function run(): void
    {
        if ($this->shouldSkipOutsideDevelopment()) {
            return;
        }

        $settings = [
            'central_pago_movil_bank_name' => '0105 - Banco Mercantil',
            'central_pago_movil_document_id' => 'J-50999888-1',
            'central_pago_movil_phone' => '0424-5556677',
            'central_pago_movil_holder_name' => 'OwoMarket C.A.',
            'central_binance_pay_id' => '987654321',
        ];

        foreach ($settings as $key => $value) {
            CentralSetting::updateOrCreate(
                ['key' => $key],
                ['id' => (string) Str::uuid(), 'value' => $value, 'type' => 'string', 'group' => 'payment']
            );
        }
    }
}
