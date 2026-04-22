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
        Schema::table('ventas', function (Blueprint $table) {
            // Fuente/origen del registro (ej: cotización generada desde el módulo 3D)
            $table->string('Origen', 40)->nullable()->after('Fecha');

            $table->foreignId('producto_id')->nullable()->after('inventario_id')
                ->constrained('productos')
                ->cascadeOnUpdate()
                ->nullOnDelete();

            // Snapshot de cálculo (para auditoría / reportes)
            $table->decimal('Area_M2', 10, 2)->nullable()->after('producto_id');
            $table->decimal('Precio_M2', 10, 2)->nullable()->after('Area_M2');
            $table->decimal('Subtotal', 10, 2)->nullable()->after('Precio_M2');
            $table->decimal('Descuento_Pct', 5, 2)->nullable()->after('Subtotal');
            $table->decimal('Descuento_Monto', 10, 2)->nullable()->after('Descuento_Pct');

            $table->decimal('Costo_M2', 10, 2)->nullable()->after('Descuento_Monto');
            $table->decimal('Costo_Total', 10, 2)->nullable()->after('Costo_M2');
            $table->decimal('Ganancia', 10, 2)->nullable()->after('Costo_Total');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('ventas', function (Blueprint $table) {
            $table->dropConstrainedForeignId('producto_id');
            $table->dropColumn([
                'Origen',
                'Area_M2',
                'Precio_M2',
                'Subtotal',
                'Descuento_Pct',
                'Descuento_Monto',
                'Costo_M2',
                'Costo_Total',
                'Ganancia',
            ]);
        });
    }
};
