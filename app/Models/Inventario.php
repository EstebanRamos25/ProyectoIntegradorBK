<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Orchid\Attachment\Attachable;
use Orchid\Filters\Filterable;
use Orchid\Screen\AsSource;

class Inventario extends Model
{
    use HasFactory, AsSource, Filterable, Attachable;

    protected $fillable = [
        "Cantidad",
        "Ubicacion",
        "Estado",
        "Codigo_Lote",
        "Tono",
        "Calibre",
        "Cajas_Entrada",
        "Cajas_Disponibles",
        "Costo_M2",
        "Fecha_Ingreso",
        "producto_id",
    ];

    public function producto()
    {
        return $this->belongsTo(Producto::class);
    }

    protected static function booted(): void
    {
        static::saving(function (Inventario $inv): void {
            // Si registran por cajas, inicializamos disponibles = entrada.
            if ($inv->Cajas_Entrada !== null && $inv->Cajas_Disponibles === null) {
                $inv->Cajas_Disponibles = (int) $inv->Cajas_Entrada;
            }

            // Compatibilidad: si solo llenan Cantidad (legacy) y no cajas, dejamos Cantidad
            // pero también podemos reflejarlo en Cajas_Disponibles para que el 3D/validaciones funcionen.
            if ($inv->Cantidad !== null && $inv->Cajas_Entrada === null && $inv->Cajas_Disponibles === null) {
                $inv->Cajas_Entrada = (int) $inv->Cantidad;
                $inv->Cajas_Disponibles = (int) $inv->Cantidad;
            }
        });
    }
}
