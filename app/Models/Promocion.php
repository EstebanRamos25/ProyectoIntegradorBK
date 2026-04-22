<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Orchid\Attachment\Attachable;
use Orchid\Filters\Filterable;
use Orchid\Screen\AsSource;

class Promocion extends Model
{
    use HasFactory, AsSource, Filterable, Attachable;
    protected $fillable = [
        "Nombre",
        "Descripcion",
        "Descuento",
        "Min_M2",
        "Activo",
    ];

    protected $casts = [
        'Descuento' => 'float',
        'Min_M2' => 'float',
        'Activo' => 'bool',
    ];
}
