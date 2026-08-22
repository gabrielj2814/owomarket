<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Hallazgo Auditoría #1: `PlatformCommission.order_id` guarda el UUID del pedido dentro de
 * la base del INQUILINO, pero las relaciones Eloquent lo declaraban contra `central_orders`.
 * Como esos dos identificadores viven en bases distintas, nunca coinciden:
 * `$centralOrder->commissions` devolvía **siempre** una colección vacía, sin error.
 *
 * Se añade `central_order_id` como clave real hacia el pedido central. `order_id` se
 * conserva tal cual y sigue apuntando al pedido de la tienda: los informes del comerciante
 * lo necesitan, y renombrarlo obligaría a tocar código que hoy funciona.
 *
 * Sin clave foránea a propósito: las comisiones del storefront no tienen pedido central, y
 * las que sí lo tienen se crean en la misma base, pero una FK obligaría a un orden de
 * borrado que hoy nadie garantiza.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('platform_commissions', function (Blueprint $table) {
            $table->string('central_order_id')->nullable()->after('order_id');
            $table->index('central_order_id');
        });

        /*
         * Relleno para las filas ya existentes. El dato correcto siempre estuvo ahí: el
         * caso de uso lo guardaba en `metadata->central_order_id` desde el principio, sólo
         * que ninguna relación lo miraba. Así que esto no inventa nada, recupera lo que ya
         * se había escrito.
         *
         * Las comisiones del storefront de tienda no llevan ese metadato y se quedan con
         * `central_order_id` a null, que es lo correcto: no vienen de un pedido central.
         */
        DB::table('platform_commissions')
            ->whereNull('central_order_id')
            ->whereNotNull('metadata')
            ->orderBy('id')
            ->each(function ($fila) {
                $metadata = json_decode((string) $fila->metadata, true);

                if (! is_array($metadata) || ! isset($metadata['central_order_id'])) {
                    return;
                }

                DB::table('platform_commissions')
                    ->where('id', $fila->id)
                    ->update(['central_order_id' => (string) $metadata['central_order_id']]);
            });
    }

    public function down(): void
    {
        Schema::table('platform_commissions', function (Blueprint $table) {
            $table->dropIndex(['central_order_id']);
            $table->dropColumn('central_order_id');
        });
    }
};
