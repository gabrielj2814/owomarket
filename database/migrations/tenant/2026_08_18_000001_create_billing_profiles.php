<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('billing_profiles', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('legal_name');                  // Razón Social / Nombre Comercial
            $table->string('tax_id');                      // Identificador Fiscal (RUT / RFC / NIF / CIF / RUC)
            $table->string('billing_email');               // Correo para notificaciones fiscales
            $table->string('phone')->nullable();
            $table->string('address_line_1');
            $table->string('address_line_2')->nullable();
            $table->string('city');
            $table->string('state');
            $table->string('postal_code');
            $table->string('country');
            $table->string('invoice_prefix')->default('FAC-'); // Prefijo de factura (ej: FAC-, INV-)
            $table->unsignedBigInteger('next_invoice_number')->default(1); // Próximo correlativo
            $table->text('invoice_footer_notes')->nullable();
            $table->string('logo_path')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('billing_profiles');
    }
};
