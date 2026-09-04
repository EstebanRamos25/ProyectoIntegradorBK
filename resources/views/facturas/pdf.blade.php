<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<style>
  * { margin: 0; padding: 0; box-sizing: border-box; }
  body { font-family: Arial, Helvetica, sans-serif; font-size: 10px; color: #111; background: #fff; }

  /* ── ENCABEZADO ── */
  .header { display: table; width: 100%; border-bottom: 2px solid #111; padding-bottom: 8px; margin-bottom: 10px; }
  .header-left  { display: table-cell; width: 55%; vertical-align: top; }
  .header-right { display: table-cell; width: 45%; vertical-align: top; text-align: right; }
  .empresa-nombre { font-size: 15px; font-weight: bold; text-transform: uppercase; }
  .empresa-sub    { font-size: 9px; color: #555; margin-top: 2px; }
  .factura-titulo { font-size: 18px; font-weight: bold; color: #1a1a1a; letter-spacing: 1px; }
  .factura-num    { font-size: 13px; font-weight: bold; color: #333; }

  /* ── CÓDIGOS SIN ── */
  .sin-block { border: 1px solid #ccc; border-radius: 4px; padding: 6px 10px; margin-bottom: 10px; font-size: 9px; background: #fafafa; }
  .sin-block table { width: 100%; }
  .sin-block td { padding: 2px 4px; }
  .sin-label { color: #555; font-weight: bold; white-space: nowrap; }
  .sin-value { font-family: 'Courier New', monospace; color: #111; }

  /* ── DATOS CLIENTE ── */
  .datos-block { display: table; width: 100%; margin-bottom: 10px; }
  .datos-col { display: table-cell; width: 50%; vertical-align: top; padding-right: 8px; }
  .datos-label { font-size: 8px; color: #777; text-transform: uppercase; font-weight: bold; margin-bottom: 2px; }
  .datos-value { font-size: 10px; font-weight: bold; }

  /* ── TABLA DE ITEMS ── */
  .items-table { width: 100%; border-collapse: collapse; margin-bottom: 10px; }
  .items-table thead th {
    background: #1a1a1a; color: #fff; padding: 5px 6px;
    font-size: 9px; text-transform: uppercase; text-align: left; border: 1px solid #1a1a1a;
  }
  .items-table thead th.right { text-align: right; }
  .items-table tbody td { padding: 5px 6px; border: 1px solid #ddd; font-size: 10px; vertical-align: top; }
  .items-table tbody td.right { text-align: right; }
  .items-table tbody tr:nth-child(even) td { background: #f8f8f8; }

  /* ── TOTALES ── */
  .totales { display: table; width: 100%; margin-bottom: 14px; }
  .totales-left  { display: table-cell; width: 55%; vertical-align: bottom; }
  .totales-right { display: table-cell; width: 45%; vertical-align: top; }
  .totales-table { width: 100%; border-collapse: collapse; }
  .totales-table td { padding: 4px 8px; font-size: 10px; }
  .totales-table .label { text-align: right; color: #555; }
  .totales-table .value { text-align: right; font-weight: bold; border-left: 1px solid #ddd; padding-left: 12px; }
  .total-row td { font-size: 13px; font-weight: bold; background: #1a1a1a; color: #fff; padding: 6px 8px; }
  .letras-box { border: 1px solid #ccc; border-radius: 4px; padding: 6px 10px; font-size: 9px; color: #444; }
  .letras-label { font-size: 8px; text-transform: uppercase; color: #777; font-weight: bold; margin-bottom: 2px; }

  /* ── PIE ── */
  .footer { border-top: 1px solid #ccc; padding-top: 8px; font-size: 8px; color: #888; }
  .footer-inner { display: table; width: 100%; }
  .footer-left  { display: table-cell; width: 50%; }
  .footer-right { display: table-cell; width: 50%; text-align: right; }
  .codigo-control { font-family: 'Courier New', monospace; font-size: 9px; color: #333; font-weight: bold; letter-spacing: 1px; }
  .estado-badge { display: inline-block; padding: 2px 8px; border-radius: 10px; font-size: 9px; font-weight: bold; text-transform: uppercase; }
  .estado-emitida { background: #d1fae5; color: #065f46; }
  .estado-anulada { background: #fee2e2; color: #991b1b; }
  .divider { border: none; border-top: 1px solid #eee; margin: 8px 0; }
</style>
</head>
<body>

{{-- ── ENCABEZADO ── --}}
<div class="header">
  <div class="header-left">
    <div class="empresa-nombre">{{ $factura->razon_social_emisor }}</div>
    <div class="empresa-sub">NIT: {{ $factura->nit_emisor }}</div>
    <div class="empresa-sub" style="margin-top:4px;">Materiales y Acabados para Construcción</div>
    <div class="empresa-sub">Santa Cruz de la Sierra, Bolivia</div>
  </div>
  <div class="header-right">
    <div class="factura-titulo">FACTURA</div>
    <div class="factura-num">N° {{ $factura->numero_factura }}</div>
    <div style="margin-top:4px;">
      <span class="estado-badge {{ $factura->estado === 'emitida' ? 'estado-emitida' : 'estado-anulada' }}">
        {{ strtoupper($factura->estado) }}
      </span>
    </div>
    <div style="font-size:9px; color:#555; margin-top:4px;">
      Fecha: {{ $factura->fecha_emision->format('d/m/Y') }}
    </div>
  </div>
</div>

{{-- ── BLOQUE AUTORIZACIÓN SIN ── --}}
<div class="sin-block">
  <table>
    <tr>
      <td class="sin-label">Código de Autorización:</td>
      <td class="sin-value">{{ $factura->codigo_autorizacion }}</td>
      <td class="sin-label" style="padding-left:20px;">Código de Control:</td>
      <td class="sin-value">{{ $factura->codigo_control }}</td>
    </tr>
  </table>
</div>

{{-- ── DATOS DEL CLIENTE ── --}}
<div class="datos-block">
  <div class="datos-col">
    <div class="datos-label">Cliente</div>
    <div class="datos-value">{{ $factura->nombre_cliente }}</div>
  </div>
  <div class="datos-col">
    <div class="datos-label">NIT / CI</div>
    <div class="datos-value">
      {{ $factura->nit_cliente }}
      @if($factura->nit_cliente === '99001')
        <span style="font-weight:normal; color:#777;">(Consumidor Final)</span>
      @endif
    </div>
  </div>
</div>

<hr class="divider">

{{-- ── TABLA DE ITEMS ── --}}
@php
  $venta   = $factura->venta;
  $product = optional($venta->producto);
@endphp

<table class="items-table">
  <thead>
    <tr>
      <th style="width:40%;">Descripción</th>
      <th class="right" style="width:15%;">Cantidad (m²)</th>
      <th class="right" style="width:20%;">Precio Unit. (Bs)</th>
      <th class="right" style="width:25%;">Subtotal (Bs)</th>
    </tr>
  </thead>
  <tbody>
    <tr>
      <td>
        <strong>{{ $product->Nombre ?? 'Producto' }}</strong>
        @if($product->Descripcion)
          <br><span style="color:#666; font-size:9px;">{{ $product->Descripcion }}</span>
        @endif
        @if($venta->Origen === '3d_sale')
          <br><span style="color:#4f46e5; font-size:8px;">▶ Venta desde experiencia 3D</span>
        @endif
      </td>
      <td class="right">{{ number_format((float)($venta->Area_M2 ?? 0), 2) }}</td>
      <td class="right">{{ number_format((float)($venta->Precio_M2 ?? 0), 2) }}</td>
      <td class="right">{{ number_format($factura->total, 2) }}</td>
    </tr>
    @if($venta->Descuento_Pct > 0)
    <tr>
      <td colspan="3" style="text-align:right; color:#e53e3e; font-style:italic;">
        Descuento aplicado ({{ $venta->Descuento_Pct }}%)
      </td>
      <td class="right" style="color:#e53e3e;">
        - Bs {{ number_format((float)$venta->Descuento_Monto, 2) }}
      </td>
    </tr>
    @endif
  </tbody>
</table>

{{-- ── TOTALES ── --}}
<div class="totales">
  <div class="totales-left">
    <div class="letras-label">Son:</div>
    <div class="letras-box">
      {{ \App\Models\Factura::numeroALetras($factura->total) }}
    </div>
  </div>
  <div class="totales-right">
    <table class="totales-table">
      <tr>
        <td class="label">Subtotal (sin IVA)</td>
        <td class="value">Bs {{ number_format($factura->subtotal_sin_iva, 2) }}</td>
      </tr>
      <tr>
        <td class="label">IVA (13%)</td>
        <td class="value">Bs {{ number_format($factura->iva_monto, 2) }}</td>
      </tr>
      <tr class="total-row">
        <td class="label" style="text-align:right; color:#fff;">TOTAL</td>
        <td class="value" style="color:#fff;">Bs {{ number_format($factura->total, 2) }}</td>
      </tr>
    </table>
  </div>
</div>

{{-- ── PIE ── --}}
<div class="footer">
  <div class="footer-inner">
    <div class="footer-left">
      Esta factura es válida como documento fiscal simulado.<br>
      Ref. Venta N° {{ $venta->id }} | Generada el {{ $factura->created_at->format('d/m/Y H:i') }}
    </div>
    <div class="footer-right">
      Código de Control:<br>
      <span class="codigo-control">{{ $factura->codigo_control }}</span>
    </div>
  </div>
</div>

</body>
</html>
