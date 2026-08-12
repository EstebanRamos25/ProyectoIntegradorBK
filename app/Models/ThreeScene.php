<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Orchid\Attachment\Attachable;
use Orchid\Filters\Filterable;
use Orchid\Screen\AsSource;

class ThreeScene extends Model
{
    use AsSource, Filterable, Attachable;

    protected $table = 'three_scenes';

    protected $fillable = [
        'user_id',
        'name',
        'data',
    ];

    protected $casts = [
        'data' => 'array',
    ];

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function quotes()
    {
        return $this->hasMany(ThreeQuote::class, 'three_scene_id');
    }
}
