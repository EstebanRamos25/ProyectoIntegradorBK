<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('productos', function (Blueprint $table) {
            // Formato real de la pieza (no de la caja): ej 45x45 cm, 20x120 cm
            $table->decimal('Ancho_Pieza_Cm', 10, 2)->nullable()->after('Unidad_Venta');
            $table->decimal('Largo_Pieza_Cm', 10, 2)->nullable()->after('Ancho_Pieza_Cm');
        });
    }

    public function down(): void
    {
        Schema::table('productos', function (Blueprint $table) {
            $table->dropColumn(['Ancho_Pieza_Cm', 'Largo_Pieza_Cm']);
        });
    }
};
