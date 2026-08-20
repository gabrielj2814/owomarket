<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('categories', function (Blueprint $table) {
            if (!Schema::hasColumn('categories', 'central_uuid')) {
                $table->string('central_uuid', 36)->nullable()->after('id')->index();
            }
        });

        Schema::table('brands', function (Blueprint $table) {
            if (!Schema::hasColumn('brands', 'central_uuid')) {
                $table->string('central_uuid', 36)->nullable()->after('id')->index();
            }
        });
    }

    public function down(): void
    {
        Schema::table('categories', function (Blueprint $table) {
            if (Schema::hasColumn('categories', 'central_uuid')) {
                $table->dropColumn('central_uuid');
            }
        });

        Schema::table('brands', function (Blueprint $table) {
            if (Schema::hasColumn('brands', 'central_uuid')) {
                $table->dropColumn('central_uuid');
            }
        });
    }
};
