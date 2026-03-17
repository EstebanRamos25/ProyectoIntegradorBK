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
                            <td>Precio estimado por m²</td>
                            <td class="money">{{ $quotation['currency_symbol'] }} {{ number_format($quotation['summary']['unit_price_m2'], 0, ',', '.') }}</td>
                        </tr>
                        <tr>
                            <td>Precio estimado por pieza</td>
                            <td class="money">{{ $quotation['currency_symbol'] }} {{ number_format($quotation['summary']['estimated_unit_price'], 0, ',', '.') }}</td>
                        </tr>
                        <tr>
                            <td>Cantidad estimada</td>
                            <td class="money">{{ number_format($quotation['summary']['estimated_units'], 0, ',', '.') }} piezas</td>
                        </tr>
                        <tr class="total-row">
                            <td>Total estimado material</td>
                            <td class="money">{{ $quotation['currency_symbol'] }} {{ number_format($quotation['summary']['estimated_total'], 0, ',', '.') }}</td>
                        </tr>
                    </tbody>
                </table>
                <div class="note">
                    Estimación referencial basada en la cobertura del piso completo. No incluye desperdicio por cortes, instalación, transporte ni accesorios adicionales.
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

    <div class="footer">
        Documento generado automáticamente desde el módulo de experiencia 3D.
    </div>
</body>
</html>
