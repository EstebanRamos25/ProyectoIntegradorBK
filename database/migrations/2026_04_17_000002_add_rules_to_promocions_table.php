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
        Schema::table('promocions', function (Blueprint $table) {
            // Promoción automática por umbral de área (m²) desde la escena 3D.
            $table->decimal('Min_M2', 10, 2)->nullable()->after('Descuento');
            $table->boolean('Activo')->default(true)->after('Min_M2');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('promocions', function (Blueprint $table) {
            $table->dropColumn(['Min_M2', 'Activo']);
        });
    }
};
