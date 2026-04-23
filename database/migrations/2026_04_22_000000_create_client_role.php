<?php

use Illuminate\Database\Migrations\Migration;
use Orchid\Platform\Models\Role;

return new class extends Migration
{
    public function up(): void
    {
        Role::query()->firstOrCreate(
            ['slug' => 'client'],
            ['name' => 'Cliente', 'permissions' => []]
        );
    }

    public function down(): void
    {
        Role::query()->where('slug', 'client')->delete();
    }
};
