<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Orchid\Attachment\Attachable;
use Orchid\Filters\Filterable;
use Orchid\Screen\AsSource;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

class Producto extends Model
{
    use HasFactory, AsSource, Filterable, Attachable, LogsActivity;

    protected $fillable = ["Nombre", "Descripcion", "Precio", "Marca", "Modelo", "Stock_Minimo", "categoria_id"];

    public function categoria()
    {
        return $this->belongsTo(Categoria::class);
    }
    public function proyectos()
{
    return $this->belongsToMany(Proyecto::class, 'proyecto__productos');
}

    // Spatie Activity Log configuration
    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->useLogName('productos')
            ->logFillable()
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs();
    }

}
