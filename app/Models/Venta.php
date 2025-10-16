<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Orchid\Attachment\Attachable;
use Orchid\Filters\Filterable;
use Orchid\Screen\AsSource;

class Venta extends Model
{
    use HasFactory, AsSource, Filterable, Attachable;

    protected $fillable = ["Total", "Fecha", "usuario_id", "promocion_id", "inventario_id"];

    public function usuario()
    {
        return $this->belongsTo(User::class);
    }

    public function promocion()
    {
        return $this->belongsTo(Promocion::class);
    }

    public function inventario()
    {
        return $this->belongsTo(Inventario::class);
    }
}
