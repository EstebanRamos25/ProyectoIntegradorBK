<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('three_quotes', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('three_scene_id')->constrained('three_scenes')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();

            // draft: generada pero no enviada | sent: enviada al admin | sold: convertida a venta
            $table->string('status', 20)->default('draft');

            $table->json('quotation');
            $table->string('pdf_path', 255)->nullable();

            $table->foreignId('producto_id')->nullable()->constrained('productos')->nullOnDelete();
            $table->integer('boxes_required')->nullable();
            $table->decimal('area_m2', 10, 2)->nullable();
            $table->decimal('total', 10, 2)->nullable();

            $table->timestamp('sent_at')->nullable();
            $table->timestamp('sold_at')->nullable();
            $table->foreignId('sold_by_user_id')->nullable()->constrained('users')->nullOnDelete();

            // Link opcional a la venta final; se deja sin FK para evitar ciclos de migración.
            $table->unsignedBigInteger('venta_id')->nullable();
            $table->index('venta_id');

            $table->timestamps();

            $table->index(['three_scene_id', 'user_id', 'created_at']);
            $table->index(['status', 'sent_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('three_quotes');
    }
};
