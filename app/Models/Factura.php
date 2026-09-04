<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Orchid\Screen\AsSource;
use Orchid\Filters\Filterable;

class Factura extends Model
{
    use HasFactory, AsSource, Filterable;

    protected $fillable = [
        'venta_id',
        'numero_factura',
        'nit_emisor',
        'razon_social_emisor',
        'nit_cliente',
        'nombre_cliente',
        'fecha_emision',
        'subtotal_sin_iva',
        'iva_monto',
        'total',
        'estado',
        'codigo_autorizacion',
        'codigo_control',
        'pdf_path',
    ];

    protected $casts = [
        'fecha_emision'   => 'date',
        'subtotal_sin_iva'=> 'float',
        'iva_monto'       => 'float',
        'total'           => 'float',
    ];

    // ──────────────────────────────────────────────────────────────
    // Relaciones
    // ──────────────────────────────────────────────────────────────

    public function venta()
    {
        return $this->belongsTo(Venta::class);
    }

    // ──────────────────────────────────────────────────────────────
    // Fábrica: genera una Factura desde una Venta confirmada
    // ──────────────────────────────────────────────────────────────

    public static function generarDesdeVenta(Venta $venta): self
    {
        // Si ya existe, la retornamos
        $existing = self::where('venta_id', $venta->id)->first();
        if ($existing) {
            return $existing;
        }

        $año   = now()->year;
        $total = (float) $venta->Total;

        // IVA 13% extraído del precio final (precio incluye IVA)
        $subtotalSinIva = round($total / 1.13, 2);
        $ivaMonto       = round($total - $subtotalSinIva, 2);

        // Número de factura: FAC-{año}-{id:04d} — se genera post-insert
        // Códigos simulados de verificación SIN
        $codigoAutorizacion = (string) random_int(1000000000000, 9999999999999);
        $codigoControl      = strtoupper(substr(md5($venta->id . $total . now()->timestamp), 0, 16));

        $factura = self::create([
            'venta_id'            => $venta->id,
            'numero_factura'      => 'TEMP', // se actualiza justo abajo
            'nit_emisor'          => config('factura.nit_emisor', '1023456789'),
            'razon_social_emisor' => config('factura.razon_social', 'Materiales 3D S.R.L.'),
            'nit_cliente'         => '99001',
            'nombre_cliente'      => optional($venta->usuario)->name ?? 'Consumidor Final',
            'fecha_emision'       => now()->toDateString(),
            'subtotal_sin_iva'    => $subtotalSinIva,
            'iva_monto'           => $ivaMonto,
            'total'               => $total,
            'estado'              => 'emitida',
            'codigo_autorizacion' => $codigoAutorizacion,
            'codigo_control'      => $codigoControl,
        ]);

        // Actualizar número de factura con el ID real
        $factura->update([
            'numero_factura' => sprintf('FAC-%d-%04d', $año, $factura->id),
        ]);

        return $factura;
    }

    // ──────────────────────────────────────────────────────────────
    // Helper: convierte monto a letras (formato boliviano)
    // ──────────────────────────────────────────────────────────────

    public function totalEnLetras(): string
    {
        return self::numeroALetras($this->total);
    }

    public static function numeroALetras(float $numero): string
    {
        $entero    = (int) floor($numero);
        $centavos  = (int) round(($numero - $entero) * 100);
        $unidades  = ['', 'UN', 'DOS', 'TRES', 'CUATRO', 'CINCO', 'SEIS', 'SIETE', 'OCHO', 'NUEVE',
                       'DIEZ', 'ONCE', 'DOCE', 'TRECE', 'CATORCE', 'QUINCE', 'DIECISÉIS',
                       'DIECISIETE', 'DIECIOCHO', 'DIECINUEVE'];
        $decenas   = ['', '', 'VEINTE', 'TREINTA', 'CUARENTA', 'CINCUENTA',
                       'SESENTA', 'SETENTA', 'OCHENTA', 'NOVENTA'];
        $centenas  = ['', 'CIENTO', 'DOSCIENTOS', 'TRESCIENTOS', 'CUATROCIENTOS',
                       'QUINIENTOS', 'SEISCIENTOS', 'SETECIENTOS', 'OCHOCIENTOS', 'NOVECIENTOS'];

        $conv = function (int $n) use ($unidades, $decenas, $centenas, &$conv): string {
            if ($n === 0)   return '';
            if ($n === 100) return 'CIEN';
            if ($n < 20)    return $unidades[$n];
            if ($n < 100) {
                $d = intdiv($n, 10);
                $u = $n % 10;
                return $decenas[$d] . ($u ? ' Y ' . $unidades[$u] : '');
            }
            if ($n < 1000) {
                $c = intdiv($n, 100);
                $r = $n % 100;
                return $centenas[$c] . ($r ? ' ' . $conv($r) : '');
            }
            if ($n < 1000000) {
                $m = intdiv($n, 1000);
                $r = $n % 1000;
                $miles = ($m === 1) ? 'MIL' : $conv($m) . ' MIL';
                return $miles . ($r ? ' ' . $conv($r) : '');
            }
            return (string) $n;
        };

        $letras = $entero === 0 ? 'CERO' : $conv($entero);
        return trim($letras) . ' ' . sprintf('%02d', $centavos) . '/100 BOLIVIANOS';
    }
}
