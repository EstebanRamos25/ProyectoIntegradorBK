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

    protected $fillable = [
        "Total",
        "Fecha",
        "Origen",
        "three_quote_id",
        "usuario_id",
        "promocion_id",
        "inventario_id",
        "producto_id",
        "Area_M2",
        "Precio_M2",
        "Subtotal",
        "Descuento_Pct",
        "Descuento_Monto",
        "Costo_M2",
        "Costo_Total",
        "Ganancia",
    ];

    protected $casts = [
        'Total' => 'float',
        'Area_M2' => 'float',
        'Precio_M2' => 'float',
        'Subtotal' => 'float',
        'Descuento_Pct' => 'float',
        'Descuento_Monto' => 'float',
        'Costo_M2' => 'float',
        'Costo_Total' => 'float',
        'Ganancia' => 'float',
        'Fecha' => 'date',
    ];

    public function usuario()
    {
        return $this->belongsTo(User::class);
    }

    public function promocion()
    {
        return $this->belongsTo(Promocion::class);
    }

    public function producto()
    {
        return $this->belongsTo(Producto::class);
    }

    public function inventario()
    {
        return $this->belongsTo(Inventario::class);
    }

    public function inventariosDescontados()
    {
        return $this->hasMany(VentaInventario::class);
    }
}
