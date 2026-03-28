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
        Schema::table('inventarios', function (Blueprint $table) {
            // Lote (producción): identifica homogeneidad (tono/calibre)
            $table->string('Codigo_Lote', 80)->nullable()->after('Estado');
            $table->string('Tono', 40)->nullable()->after('Codigo_Lote');
            $table->string('Calibre', 40)->nullable()->after('Tono');

            // Cantidad por empaque (cajas): mantenemos 'Cantidad' por compatibilidad,
            // pero agregamos campos más explícitos para disponibilidad.
            $table->integer('Cajas_Entrada')->nullable()->after('Calibre');
            $table->integer('Cajas_Disponibles')->nullable()->after('Cajas_Entrada');

            $table->date('Fecha_Ingreso')->nullable()->after('Cajas_Disponibles');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('inventarios', function (Blueprint $table) {
            $table->dropColumn([
                'Codigo_Lote',
                'Tono',
                'Calibre',
                'Cajas_Entrada',
                'Cajas_Disponibles',
                'Fecha_Ingreso',
            ]);
        });
    }
};
