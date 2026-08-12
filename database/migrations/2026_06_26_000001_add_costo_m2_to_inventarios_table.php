<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('inventarios', function (Blueprint $table): void {
            if (!Schema::hasColumn('inventarios', 'Costo_M2')) {
                $table->decimal('Costo_M2', 10, 2)->nullable()->after('Cajas_Disponibles');
            }
        });
    }

    public function down(): void
    {
        Schema::table('inventarios', function (Blueprint $table): void {
            if (Schema::hasColumn('inventarios', 'Costo_M2')) {
                $table->dropColumn('Costo_M2');
            }
        });
    }
};