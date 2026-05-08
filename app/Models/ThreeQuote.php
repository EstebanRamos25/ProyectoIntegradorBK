<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Orchid\Attachment\Attachable;
use Orchid\Filters\Filterable;
use Orchid\Screen\AsSource;

class ThreeQuote extends Model
{
    use AsSource, Filterable, Attachable;

    protected $table = 'three_quotes';

    protected $fillable = [
        'three_scene_id',
        'user_id',
        'status',
        'quotation',
        'pdf_path',
        'producto_id',
        'boxes_required',
        'area_m2',
        'total',
        'sent_at',
        'sold_at',
        'sold_by_user_id',
        'venta_id',
    ];

    protected $casts = [
        'quotation' => 'array',
        'boxes_required' => 'int',
        'area_m2' => 'float',
        'total' => 'float',
        'sent_at' => 'datetime',
        'sold_at' => 'datetime',
    ];

    public function scene()
    {
        return $this->belongsTo(ThreeScene::class, 'three_scene_id');
    }

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function soldBy()
    {
        return $this->belongsTo(User::class, 'sold_by_user_id');
    }

    public function producto()
    {
        return $this->belongsTo(Producto::class, 'producto_id');
    }

    public function venta()
    {
        return $this->belongsTo(Venta::class, 'venta_id');
    }
}
