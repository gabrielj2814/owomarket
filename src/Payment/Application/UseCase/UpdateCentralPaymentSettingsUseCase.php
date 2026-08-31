<?php

declare(strict_types=1);

namespace Src\Payment\Application\UseCase;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Src\Payment\Infrastructure\Eloquent\Models\CentralSetting;

/**
 * Guarda los datos de cobro de la plataforma (hallazgo N33).
 *
 * La Fase 3.4 dejo el checkout central leyendo `central_settings`, pero **no habia donde
 * escribirlos**: solo los ponia un seeder de desarrollo o un INSERT a mano. Hasta que se
 * cargan, el checkout central no ofrece ningun metodo de pago — por diseno, para no
 * repetir G1 y mandar dinero a una cuenta inventada.
 */
final class UpdateCentralPaymentSettingsUseCase
{
    /**
     * Claves que gobierna esta pantalla. Cualquier otra que llegue se ignora.
     */
    public const KEYS = [
        'central_pago_movil_bank_name',
        'central_pago_movil_document_id',
        'central_pago_movil_phone',
        'central_pago_movil_holder_name',
        'central_binance_pay_id',
        // Fase 4c: coste fijo de una transferencia a un banco distinto del de la plataforma.
        // Va aqui y no en un ajuste de tienda porque es una condicion de la plataforma, la
        // misma para todas. El banco propio ya esta arriba, en `central_pago_movil_bank_name`.
        'central_interbank_transfer_fee',
        // Dias que un pedido entregado espera antes de que su importe sea retirable. Es la
        // ventana en la que el comprador puede pedir una devolucion o reclamar garantia: si el
        // dinero ya salio, atenderla es perseguirlo.
        'central_payout_hold_days',
    ];

    /**
     * @param  array<string, string|null>  $settings
     * @return array<string, string> Los ajustes tal como quedaron guardados.
     */
    public function execute(array $settings): array
    {
        // Todo o nada: unos datos de cobro a medias son justo lo que no queremos que vea
        // el comprador.
        DB::transaction(function () use ($settings): void {
            foreach (self::KEYS as $key) {
                if (! array_key_exists($key, $settings)) {
                    continue;
                }

                $value = is_string($settings[$key]) ? trim($settings[$key]) : '';

                CentralSetting::updateOrCreate(
                    ['key' => $key],
                    [
                        'id' => (string) Str::uuid(),
                        'value' => $value !== '' ? $value : null,
                        'type' => 'string',
                        'group' => 'payment',
                    ]
                );
            }
        });

        return self::current();
    }

    /**
     * @return array<string, string>
     */
    public static function current(): array
    {
        return CentralSetting::query()
            ->where('group', 'payment')
            ->whereIn('key', self::KEYS)
            ->pluck('value', 'key')
            ->map(fn ($v) => (string) $v)
            ->all();
    }
}
