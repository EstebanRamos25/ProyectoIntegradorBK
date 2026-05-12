<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('three_interaction_events', function (Blueprint $table): void {
            $table->id();

            $table->unsignedBigInteger('user_id');
            $table->unsignedBigInteger('three_scene_id');

            $table->unsignedBigInteger('producto_id')->nullable();
            $table->unsignedBigInteger('categoria_id')->nullable();

            $table->string('event_type', 50);
            $table->float('value')->nullable();
            $table->json('meta')->nullable();

            $table->timestamps();

            $table->index(['user_id', 'three_scene_id', 'created_at'], 'three_ev_user_scene_time');
            $table->index(['event_type', 'created_at'], 'three_ev_type_time');
            $table->index(['producto_id', 'created_at'], 'three_ev_producto_time');
            $table->index(['categoria_id', 'created_at'], 'three_ev_categoria_time');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('three_interaction_events');
    }
};
