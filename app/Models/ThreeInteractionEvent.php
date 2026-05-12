<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ThreeInteractionEvent extends Model
{
    protected $table = 'three_interaction_events';

    protected $fillable = [
        'user_id',
        'three_scene_id',
        'producto_id',
        'categoria_id',
        'event_type',
        'value',
        'meta',
    ];

    protected $casts = [
        'meta' => 'array',
        'value' => 'float',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function scene()
    {
        return $this->belongsTo(ThreeScene::class, 'three_scene_id');
    }

    public function producto()
    {
        return $this->belongsTo(Producto::class, 'producto_id');
    }

    public function categoria()
    {
        return $this->belongsTo(Categoria::class, 'categoria_id');
    }
}
