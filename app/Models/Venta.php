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
        "Origen",          // '3d_sale' para ventas desde la experiencia 3D
        "three_quote_id",
        "usuario_id",
        "promocion_id",
        "inventario_id",   // @legacy: primer lote descontado (referencia rápida). El detalle real está en VentaInventario.
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

    /**
     * @legacy Referencia al primer inventario descontado.
     * Para el detalle completo de cajas por lote, usar inventariosDescontados().
     */
    public function inventario()
    {
        return $this->belongsTo(Inventario::class);
    }

    /**
     * Relación principal (nuevo flujo): detalle de inventarios descontados por lote FIFO.
     */
    public function inventariosDescontados()
    {
        return $this->hasMany(VentaInventario::class);
    }

    /**
     * Cotización 3D que originó esta venta (si proviene del flujo 3D).
     */
    public function threeQuote()
    {
        return $this->belongsTo(ThreeQuote::class, 'three_quote_id');
    }

    /**
     * Factura generada para esta venta (si fue facturada).
     */
    public function factura()
    {
        return $this->hasOne(\App\Models\Factura::class);
    }
}
