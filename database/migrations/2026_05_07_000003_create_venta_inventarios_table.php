<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('venta_inventarios', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('venta_id')->constrained('ventas')->cascadeOnDelete();
            $table->foreignId('inventario_id')->constrained('inventarios')->cascadeOnDelete();
            $table->integer('cajas_descontadas');
            $table->timestamps();

            $table->index(['venta_id', 'inventario_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('venta_inventarios');
    }
};
