<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Orchid\Attachment\Attachable;
use Orchid\Filters\Filterable;
use Orchid\Screen\AsSource;

class Proyecto extends Model
{
    use HasFactory, AsSource, Filterable, Attachable;

    protected $fillable = ["Nombre", "Descripcion", "Tipo_Proyecto", "user_id", "producto_id"];

    // Relacin correcta al usuario
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    // Proyecto pertenece a un Producto (FK producto_id)
    public function producto()
    {
        return $this->belongsTo(Producto::class);
    }

}
