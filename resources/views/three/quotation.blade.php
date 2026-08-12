<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Cotización escena 3D</title>
    <style>
        @page {
            margin: 26px 24px;
        }

        body {
            font-family: DejaVu Sans, sans-serif;
            font-size: 11px;
            color: #1f2937;
            margin: 0;
            line-height: 1.45;
            background: #f8fafc;
        }

        .hero {
            background: #0f172a;
            color: #fff;
            border-radius: 18px;
            padding: 22px 24px;
            margin-bottom: 18px;
            border: 1px solid #1e3a8a;
        }

        .eyebrow {
            text-transform: uppercase;
            letter-spacing: 1.5px;
            font-size: 10px;
            color: #bfdbfe;
            margin-bottom: 8px;
        }

        h1 {
            margin: 0 0 8px;
            font-size: 26px;
            color: #ffffff;
        }

        .subtitle {
            font-size: 12px;
            margin: 0;
            color: #e2e8f0;
        }

        .meta {
            margin-top: 14px;
            font-size: 10px;
            color: #cbd5e1;
        }

        .section-title {
            margin: 18px 0 10px;
            font-size: 15px;
            font-weight: bold;
            color: #0f172a;
        }

        .grid {
            width: 100%;
            margin: 0 -1%;
        }

        .col-4,
        .col-6,
        .col-12 {
            display: inline-block;
            vertical-align: top;
            margin: 0 1% 12px;
        }

        .col-4 { width: 31.3%; }
        .col-6 { width: 48%; }
        .col-12 { width: 98%; }

        .card {
            background: #fff;
            border: 1px solid #e2e8f0;
            border-radius: 16px;
            padding: 14px 16px;
        }

        .card-title {
            font-size: 11px;
            color: #64748b;
            text-transform: uppercase;
            letter-spacing: 1px;
            margin-bottom: 8px;
        }

        .card-value {
            font-size: 24px;
            font-weight: bold;
            color: #0f172a;
        }

        table {
            width: 100%;
            border-collapse: collapse;
        }

        th,
        td {
            padding: 9px 8px;
            border-bottom: 1px solid #e2e8f0;
            text-align: left;
            vertical-align: top;
        }

        th {
            font-size: 10px;
            text-transform: uppercase;
            letter-spacing: .8px;
            color: #64748b;
        }

        td strong {
            color: #0f172a;
        }

        .money {
            text-align: right;
            white-space: nowrap;
        }

        .plan-box {
            padding: 12px;
            border: 1px solid #dbeafe;
            border-radius: 16px;
            background: #eff6ff;
        }

        .note {
            font-size: 10px;
            color: #64748b;
            margin-top: 10px;
        }

        .total-row td {
            font-size: 13px;
            font-weight: bold;
            color: #0f172a;
        }

        .footer {
            margin-top: 14px;
            font-size: 9px;
            color: #94a3b8;
            text-align: center;
        }
    </style>
</head>
<body>
    <div class="hero">
        <div class="eyebrow">Proyecto Integrador BK</div>
        <h1>Cotización estimada</h1>
        <p class="subtitle">{{ $quotation['scene_name'] }}</p>
        <div class="meta">
            Generado: {{ $quotation['generated_at']->format('d/m/Y H:i:s') }} · Tipo de piso: {{ $quotation['floor_kind'] }}
            @if(!empty($quotation['walls']))
                · Cobertura paredes: {{ number_format((float) ($quotation['walls']['wall_area_m2'] ?? 0), 2, ',', '.') }} m²
            @endif
        </div>
    </div>

    <div class="grid">
        <div class="col-4">
            <div class="card">
                <div class="card-title">Área del cuarto</div>
                <div class="card-value">{{ number_format($quotation['summary']['floor_area_m2'], 2, ',', '.') }} m²</div>
            </div>
        </div>
        <div class="col-4">
            <div class="card">
                <div class="card-title">Elemento cotizado</div>
                <div class="card-value" style="font-size: 21px;">{{ $quotation['piece']['label'] }}</div>
            </div>
        </div>
        <div class="col-4">
            <div class="card">
                <div class="card-title">Cantidad estimada</div>
                <div class="card-value">{{ number_format($quotation['summary']['estimated_units'], 0, ',', '.') }}</div>
            </div>
        </div>
    </div>

    @if(!empty($quotation['walls']))
        <div class="grid">
            <div class="col-4">
                <div class="card">
                    <div class="card-title">Área paredes</div>
                    <div class="card-value">{{ number_format((float) ($quotation['walls']['wall_area_m2'] ?? 0), 2, ',', '.') }} m²</div>
                </div>
            </div>
            <div class="col-4">
                <div class="card">
                    <div class="card-title">Material paredes</div>
                    <div class="card-value" style="font-size: 16px;">
                        {{ (string) data_get($quotation, 'walls.material.name', '—') }}
                    </div>
                </div>
            </div>
            <div class="col-4">
                <div class="card">
                    <div class="card-title">Piezas estimadas (paredes)</div>
                    <div class="card-value">
                        {{ data_get($quotation, 'walls.estimated_units') !== null ? number_format((int) $quotation['walls']['estimated_units'], 0, ',', '.') : '—' }}
                    </div>
                </div>
            </div>
        </div>
    @endif

    <div class="section-title">Medidas consideradas</div>
    <div class="grid">
        <div class="col-6">
            <div class="card">
                <table>
                    <tbody>
                        <tr>
                            <th>Cuarto</th>
                            <td>
                                <strong>Ancho:</strong> {{ number_format($quotation['room']['width_cm'], 0, ',', '.') }} cm<br>
                                <strong>Largo:</strong> {{ number_format($quotation['room']['depth_cm'], 0, ',', '.') }} cm<br>
                                <strong>Alto:</strong> {{ number_format($quotation['room']['height_cm'], 0, ',', '.') }} cm
                            </td>
                        </tr>
                        <tr>
                            <th>Elemento</th>
                            <td>
                                <strong>{{ $quotation['piece']['label'] }}</strong><br>
                                {{ number_format($quotation['piece']['width_cm'], 0, ',', '.') }} × {{ number_format($quotation['piece']['depth_cm'], 0, ',', '.') }} cm
                                @if(!empty($quotation['piece']['height_cm']))
                                    · espesor aprox. {{ number_format($quotation['piece']['height_cm'], 0, ',', '.') }} cm
                                @endif
                            </td>
                        </tr>
                        <tr>
                            <th>Área unitaria</th>
                            <td>{{ number_format($quotation['summary']['piece_area_m2'], 4, ',', '.') }} m² por pieza</td>
                        </tr>
                        @if(!empty($quotation['walls']))
                            <tr>
                                <th>Paredes</th>
                                <td>
                                    <strong>Área:</strong> {{ number_format((float) ($quotation['walls']['wall_area_m2'] ?? 0), 2, ',', '.') }} m²<br>
                                    <strong>Área pieza:</strong>
                                    {{ data_get($quotation, 'walls.piece_area_m2') !== null ? number_format((float) $quotation['walls']['piece_area_m2'], 4, ',', '.') . ' m²' : '—' }}
                                    <br>
                                    <strong>Piezas estimadas:</strong>
                                    {{ data_get($quotation, 'walls.estimated_units') !== null ? number_format((int) $quotation['walls']['estimated_units'], 0, ',', '.') : '—' }}
                                    <br>
                                    <strong>Cajas requeridas:</strong>
                                    {{ data_get($quotation, 'walls.boxes_required') !== null ? number_format((int) $quotation['walls']['boxes_required'], 0, ',', '.') : '—' }}
                                </td>
                            </tr>
                        @endif
                    </tbody>
                </table>
            </div>
        </div>
        <div class="col-6">
            <div class="card">
                <table>
                    <thead>
                        <tr>
                            <th>Concepto</th>
                            <th class="money">Valor</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <td>Precio estimado por m² (Piso)</td>
                            <td class="money">{{ $quotation['currency_symbol'] }} {{ number_format($quotation['summary']['unit_price_m2'], 0, ',', '.') }}</td>
                        </tr>
                        <tr>
                            <td>Precio estimado por pieza (Piso)</td>
                            <td class="money">{{ $quotation['currency_symbol'] }} {{ number_format($quotation['summary']['estimated_unit_price'], 0, ',', '.') }}</td>
                        </tr>
                        <tr>
                            <td>Cantidad estimada (Piso)</td>
                            <td class="money">{{ number_format($quotation['summary']['estimated_units'], 0, ',', '.') }} piezas</td>
                        </tr>
                        <tr>
                            <td>Subtotal estimado material (Piso)</td>
                            <td class="money">{{ $quotation['currency_symbol'] }} {{ number_format($quotation['summary']['subtotal'] ?? $quotation['summary']['estimated_total'], 0, ',', '.') }}</td>
                        </tr>
                        @if(!empty($quotation['promotion']))
                            <tr>
                                <td>Promoción aplicada (Piso)</td>
                                <td class="money">
                                    {{ $quotation['promotion']['name'] }}
                                    ({{ number_format((float) $quotation['promotion']['discount_pct'], 2, ',', '.') }}%)
                                </td>
                            </tr>
                            <tr>
                                <td>Descuento (Piso)</td>
                                <td class="money">- {{ $quotation['currency_symbol'] }} {{ number_format((float) ($quotation['summary']['discount_amount'] ?? 0), 0, ',', '.') }}</td>
                            </tr>
                            <tr class="total-row">
                                <td>Total con descuento (Piso)</td>
                                <td class="money">{{ $quotation['currency_symbol'] }} {{ number_format((float) ($quotation['summary']['total_after_discount'] ?? $quotation['summary']['estimated_total']), 0, ',', '.') }}</td>
                            </tr>
                        @else
                            <tr class="total-row">
                                <td>Total estimado material (Piso)</td>
                                <td class="money">{{ $quotation['currency_symbol'] }} {{ number_format($quotation['summary']['estimated_total'], 0, ',', '.') }}</td>
                            </tr>
                        @endif
                        
                        @if(!empty($quotation['walls']))
                            <tr>
                                <td colspan="2" style="padding-top: 15px; border-bottom: none;"><strong style="font-size: 12px;">Paredes</strong></td>
                            </tr>
                            <tr>
                                <td>Precio estimado por m² (Paredes)</td>
                                <td class="money">{{ $quotation['currency_symbol'] }} {{ number_format($quotation['walls']['unit_price_m2'], 0, ',', '.') }}</td>
                            </tr>
                            <tr class="total-row">
                                <td>Total estimado material (Paredes)</td>
                                <td class="money">{{ $quotation['currency_symbol'] }} {{ number_format($quotation['walls']['estimated_total'], 0, ',', '.') }}</td>
                            </tr>
                            
                            <tr class="total-row" style="background-color: #f1f5f9;">
                                <td style="padding-top: 15px;">TOTAL GENERAL (Piso + Paredes)</td>
                                <td class="money" style="padding-top: 15px;">{{ $quotation['currency_symbol'] }} {{ number_format(($quotation['summary']['total_after_discount'] ?? $quotation['summary']['estimated_total']) + $quotation['walls']['estimated_total'], 0, ',', '.') }}</td>
                            </tr>
                        @endif
                    </tbody>
                </table>
                <div class="note">
                    Estimación referencial basada en la cobertura del piso y paredes. No incluye desperdicio por cortes, instalación, transporte ni accesorios adicionales.
                    @if($quotation['prices_are_reference'])
                    @endif
                </div>
            </div>
        </div>
    </div>

    <div class="section-title">Plano superior referencial</div>
    <div class="card plan-box">
        {!! $quotation['plan_svg'] !!}
        <div class="note">
            El plano muestra una proyección superior para visualizar cómo se distribuiría el elemento sobre el área configurada en la escena 3D.
        </div>
    </div>

    @if(!empty($quotation['snapshot_top_png_data_url']))
        <div class="section-title">Captura superior (escena 3D)</div>
        <div class="card" style="text-align: center;">
            <img
                src="{{ $quotation['snapshot_top_png_data_url'] }}"
                alt="Captura superior escena 3D"
                style="width: 100%; max-width: 520px; border-radius: 14px; border: 1px solid #e2e8f0;"
            />
            <div class="note">
                Imagen generada automáticamente desde el canvas 3D. Puede variar según la carga de texturas.
            </div>
        </div>
    @endif

    <div class="footer">
        Documento generado automáticamente desde el módulo de experiencia 3D.
    </div>
</body>
</html>
