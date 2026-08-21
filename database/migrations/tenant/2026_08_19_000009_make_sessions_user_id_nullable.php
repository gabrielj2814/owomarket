<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Hallazgo F1: repara las bases creadas antes de que se corrigiera la migración
 * original, donde `sessions.user_id` es NOT NULL con clave foránea a `users`.
 *
 * `DatabaseSessionHandler::addUserInformation()` escribe `user_id => auth()->id()`,
 * que es null para cualquier visitante anónimo, así que con `SESSION_DRIVER=database`
 * la sesión no se persistía: sin sesión no hay token CSRF, y sin token CSRF nadie
 * puede iniciar sesión. Las bases nuevas ya nacen correctas.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('sessions')) {
            return;
        }

        // SQLite no admite eliminar una clave foránea sobre una tabla existente, y las
        // bases SQLite del proyecto (la suite de tests) se crean siempre desde cero, así
        // que ya nacen con la migración original corregida.
        if (Schema::getConnection()->getDriverName() === 'sqlite') {
            return;
        }

        $hasUserForeignKey = collect(Schema::getForeignKeys('sessions'))
            ->contains(fn (array $foreignKey) => in_array('user_id', $foreignKey['columns'], true));

        if ($hasUserForeignKey) {
            Schema::table('sessions', function (Blueprint $table) {
                $table->dropForeign(['user_id']);
            });
        }

        Schema::table('sessions', function (Blueprint $table) {
            $table->string('user_id')->nullable()->change();
        });
    }

    public function down(): void
    {
        // Deliberadamente vacía: revertir devolvería la columna a NOT NULL con la clave
        // foránea, que es exactamente el estado que rompe el login. Además fallaría en
        // cuanto exista una sola sesión de visitante anónimo con `user_id` nulo.
    }
};
