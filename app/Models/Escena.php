<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Orchid\Attachment\Attachable;
use Orchid\Filters\Filterable;
use Orchid\Screen\AsSource;

class Escena extends Model
{
    use HasFactory, AsSource, Filterable, Attachable;

    protected $fillable = ["Nombre", "Descripcion", "Tipo_Diseño", "proyecto_id"];

    public function proyecto()
    {
        return $this->belongsTo(Proyecto::class);
    }
}
