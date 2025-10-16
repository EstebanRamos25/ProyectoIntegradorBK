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
        Schema::create('productos', function (Blueprint $table) {
            $table->id();
            $table->string('Nombre', 100)->nullable();
            $table->text('Descripcion')->nullable();
            $table->decimal('Precio', 10, 2)->nullable();
            $table->string('Marca', 100)->nullable();
            $table->string('Modelo', 100)->nullable();
            $table->integer('Stock_Minimo')->nullable();
            $table->foreignId('categoria_id')->nullable()->constrained('categorias')->cascadeOnUpdate()->cascadeOnDelete();
            $table->timestamps();
        });
        
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('productos');
    }
};
