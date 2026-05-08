<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('ventas', function (Blueprint $table): void {
            if (!Schema::hasColumn('ventas', 'three_quote_id')) {
                $table->foreignId('three_quote_id')
                    ->nullable()
                    ->after('Origen')
                    ->constrained('three_quotes')
                    ->nullOnDelete();
            }
        });
    }

    public function down(): void
    {
        Schema::table('ventas', function (Blueprint $table): void {
            if (Schema::hasColumn('ventas', 'three_quote_id')) {
                $table->dropConstrainedForeignId('three_quote_id');
            }
        });
    }
};
