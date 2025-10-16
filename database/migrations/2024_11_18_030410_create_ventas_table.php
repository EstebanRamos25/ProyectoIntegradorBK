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
        Schema::create('ventas', function (Blueprint $table) {
            $table->id();
            $table->decimal('Total', 10, 2)->nullable();
            $table->date('Fecha')->nullable();
            $table->foreignId('usuario_id')->nullable()->constrained('users')->cascadeOnUpdate()->cascadeOnDelete();
            $table->foreignId('promocion_id')->nullable()->constrained('promocions')->cascadeOnUpdate()->cascadeOnDelete();
            $table->foreignId('inventario_id')->nullable()->constrained('inventarios')->cascadeOnUpdate()->cascadeOnDelete();
            $table->timestamps();

        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('ventas');
    }
};
