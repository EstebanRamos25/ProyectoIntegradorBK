<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class VentaInventario extends Model
{
    protected $table = 'venta_inventarios';

    protected $fillable = [
        'venta_id',
        'inventario_id',
        'cajas_descontadas',
    ];

    protected $casts = [
        'cajas_descontadas' => 'int',
    ];

    public function venta()
    {
        return $this->belongsTo(Venta::class);
    }

    public function inventario()
    {
        return $this->belongsTo(Inventario::class);
    }
}
