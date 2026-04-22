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

    protected $fillable = [
        "Nombre",
        "Descripcion",
        "Precio",
        "Costo_M2",
        "M2_Por_Caja",
        "Piezas_Por_Caja",
        "Unidad_Venta",
        "Ancho_Pieza_Cm",
        "Largo_Pieza_Cm",
        "Marca",
        "Modelo",
        "Stock_Minimo",
        "categoria_id",
    ];

    protected $casts = [
        'Precio' => 'float',
        'Costo_M2' => 'float',
        'M2_Por_Caja' => 'float',
        'Piezas_Por_Caja' => 'int',
        'Ancho_Pieza_Cm' => 'float',
        'Largo_Pieza_Cm' => 'float',
        'Stock_Minimo' => 'int',
    ];

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
