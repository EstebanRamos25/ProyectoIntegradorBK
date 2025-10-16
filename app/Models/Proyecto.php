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

    public function usuario()
    {
        return $this->belongsTo(User::class); // Reemplaza 'User' por el nombre del modelo correcto si es necesario.
    }

    public function productos()
{
    return $this->belongsTo(Producto::class);
}

}
