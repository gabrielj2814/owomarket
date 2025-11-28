<?php

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
        Schema::create('auth_users', function (Blueprint $table) {
            $table->uuid("id")->primary();
            $table->string("user_id")->unique();
            $table->string("user_name");
            $table->string("user_email");
            $table->string("user_type");
            $table->string("user_avatar")->nullable();
            $table->string("user_is_active");
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('auth_users');
    }
};
