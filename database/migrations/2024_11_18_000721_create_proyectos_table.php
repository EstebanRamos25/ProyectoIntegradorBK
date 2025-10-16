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
    Schema::create('proyectos', function (Blueprint $table) {
        $table->id();
            $table->string('Nombre', 100)->nullable();
            $table->text('Descripcion')->nullable();
            $table->string('Tipo_Proyecto', 50)->nullable();
            $table->foreignId('user_id')->nullable()->constrained('users')->cascadeOnUpdate()->cascadeOnDelete();

            $table->foreignId('producto_id')->nullable()->constrained('productos')->cascadeOnUpdate()->cascadeOnDelete();
            $table->timestamps();

    });
    
}


    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('proyectos');
    }
};
