<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ThreeScene extends Model
{
    protected $table = 'three_scenes';

    protected $fillable = [
        'user_id',
        'name',
        'data',
    ];

    protected $casts = [
        'data' => 'array',
    ];
}
