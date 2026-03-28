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
        Schema::table('productos', function (Blueprint $table) {
            // Empaque / unidad de venta
            // Ej: 1 caja = 1.80 m2, 10 piezas
            $table->decimal('M2_Por_Caja', 10, 4)->nullable()->after('Precio');
            $table->integer('Piezas_Por_Caja')->nullable()->after('M2_Por_Caja');
            $table->string('Unidad_Venta', 30)->nullable()->after('Piezas_Por_Caja');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('productos', function (Blueprint $table) {
            $table->dropColumn(['M2_Por_Caja', 'Piezas_Por_Caja', 'Unidad_Venta']);
        });
    }
};
