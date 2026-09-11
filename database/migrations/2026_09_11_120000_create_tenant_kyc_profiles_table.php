<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * KYC del comerciante (subsistema 1 de
 * `planes/anotaciones/DECISION_GARANTIAS_Y_RESPONSABILIDAD.md`).
 *
 * ## Por que una tabla propia y no las columnas que ya existian
 *
 * `users` ya traia `cedula`, `nacionalidad` y `cedula_doc` desde la migracion inicial, muertas
 * --ninguna linea de la aplicacion las tocaba-- y lo natural parecia revivirlas.
 *
 * No se hace por un motivo concreto: **hay al menos dos modelos Eloquent distintos leyendo esa
 * misma tabla** (`Src\Admin\...\User` y `Src\User\...\User`), y el cast `encrypted` se declara
 * POR MODELO. Uno escribiria cifrado y el otro texto plano en la misma columna, y nadie se
 * enteraria hasta que alguien intentara descifrar un numero que nunca se cifro. En una columna
 * con documentos de identidad, eso no es un bug: es una fuga.
 *
 * Una tabla con un unico modelo hace ese fallo imposible, y ademas evita la ambiguedad de que
 * `users` existe en la base central Y en la de cada inquilino.
 *
 * ## Por que cada dato sensible va DOS veces
 *
 * La decision exige poder **bloquear una cedula o un RIF para que no abran otra tienda**. Pero
 * un dato cifrado no se puede buscar: el cast `encrypted` usa un IV aleatorio, asi que el mismo
 * numero cifrado dos veces da dos valores distintos y `where('cedula', $valor)` no encuentra
 * nada nunca.
 *
 * De ahi el par: **la columna cifrada para leerla** y **el hash para buscarla**. Sin el hash,
 * la regla de «no puede reabrir con otro nombre» seria inaplicable — que es justo el valor
 * principal que la decision le atribuye al KYC.
 *
 * Los hashes NO llevan `unique`: un mismo dueño con dos tiendas es legitimo. Lo que hace falta
 * es poder consultar por identidad, no impedir la repeticion.
 *
 * ## Que se cifra y que no
 *
 * Se cifran **cedula y RIF**: son los identificadores con los que se suplanta a una persona.
 * `phone` y `address` quedan en claro porque son datos de contacto que la pantalla del
 * administrador necesita mostrar y filtrar, y porque la aplicacion ya los guarda asi en
 * `users` y en la libreta de direcciones — cifrarlos solo aqui seria teatro incoherente.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tenant_kyc_profiles', function (Blueprint $table) {
            $table->uuid('id')->primary();

            // Una tienda, un expediente. El RIF es del negocio, asi que dos tiendas del mismo
            // dueño tienen expedientes distintos aunque compartan cedula.
            $table->string('tenant_id')->unique();
            // El responsable: la persona fisica contra la que se dirigiria una reclamacion.
            $table->string('user_id')->nullable()->index();

            // Nombre tal como figura en el documento, que no tiene por que ser el nombre
            // comercial ni el del perfil.
            $table->string('legal_name');

            $table->text('cedula');
            $table->char('cedula_hash', 64)->index();
            $table->char('nationality', 1)->default('V');

            $table->text('rif')->nullable();
            $table->char('rif_hash', 64)->nullable()->index();

            $table->string('phone');
            $table->text('address');

            // Opcional: la foto del documento. Se decidio no exigirla para no convertir a la
            // plataforma en custodio de imagenes de identidad desde el primer dia --si no se
            // guarda, no se puede filtrar-- pero se admite si el comerciante la aporta.
            $table->string('document_path')->nullable();

            $table->enum('status', ['pending', 'verified', 'rejected'])->default('pending');
            $table->timestamp('reviewed_at')->nullable();
            $table->string('reviewed_by')->nullable();
            $table->text('rejection_reason')->nullable();

            $table->timestamps();

            $table->foreign('tenant_id')->references('id')->on('tenants')->onDelete('cascade');
            // Para la pantalla del administrador: lo pendiente de revisar, lo mas viejo
            // primero. Nombre corto explicito, que el autogenerado pasaria de 64 caracteres.
            $table->index(['status', 'created_at'], 'kyc_pendientes_index');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tenant_kyc_profiles');
    }
};
