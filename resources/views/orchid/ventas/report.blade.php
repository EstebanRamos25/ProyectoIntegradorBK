<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Reporte de ganancias</title>
    <style>
        @page { margin: 22px 18px; }

        * { box-sizing: border-box; }

        body {
            font-family: DejaVu Sans, sans-serif;
            color: #0f172a;
            font-size: 10px;
            line-height: 1.45;
            margin: 0;
            background: #ffffff;
        }

        .hero {
            background: #0f172a;
            color: #ffffff;
            border-radius: 18px;
            padding: 18px 22px;
            margin-bottom: 14px;
            border: 1px solid #1e3a8a;
        }

        .eyebrow {
            font-size: 9px;
            letter-spacing: 1.6px;
            text-transform: uppercase;
            color: #bfdbfe;
            margin-bottom: 8px;
        }

        h1 {
            margin: 0 0 6px;
            font-size: 22px;
            line-height: 1.1;
            color: #ffffff;
        }

        .subtitle {
            margin: 0;
            font-size: 11px;
            color: #e2e8f0;
        }

        .meta {
            margin-top: 10px;
            font-size: 9px;
            color: #cbd5e1;
        }

        .section-title {
            font-size: 13px;
            font-weight: bold;
            color: #0f172a;
            margin: 14px 0 8px;
        }

        .row { width: 100%; margin: 0 -1%; }

        .col-3, .col-6 {
            display: inline-block;
            vertical-align: top;
            margin: 0 1% 10px;
        }

        .col-3 { width: 23%; }
        .col-6 { width: 48%; }

        .stat-card {
            background: #ffffff;
            border-radius: 14px;
            border: 1px solid #e5e7eb;
            padding: 12px 14px;
            min-height: 72px;
        }

        .stat-label {
            font-size: 9px;
            text-transform: uppercase;
            letter-spacing: 1px;
            color: #64748b;
            margin-bottom: 8px;
        }

        .stat-value { font-size: 18px; font-weight: bold; color: #0f172a; }

        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 10px;
        }

        th, td {
            border: 1px solid #e5e7eb;
            padding: 6px 6px;
            vertical-align: top;
        }

        th {
            background: #f1f5f9;
            color: #0f172a;
            font-size: 9px;
            text-transform: uppercase;
            letter-spacing: .7px;
        }

        td { color: #0f172a; }

        .muted { color: #64748b; }

        .nowrap { white-space: nowrap; }

        .right { text-align: right; }

        .footer {
            margin-top: 10px;
            text-align: center;
            color: #94a3b8;
            font-size: 9px;
        }
    </style>
</head>
<body>
    <div class="hero">
        <div class="eyebrow">Proyecto Integrador BK</div>
        <h1>Reporte de ganancias (3D)</h1>
        <p class="subtitle">Ganancia estimada a partir del costo por m² configurado en Productos.</p>
        <div class="meta">
            Generado: {{ $generatedAt->format('d/m/Y H:i:s') }}
            @if(($filters['from'] ?? null) || ($filters['to'] ?? null))
                &nbsp;|&nbsp; Periodo: {{ optional($filters['from'])->format('d/m/Y') ?: '—' }} - {{ optional($filters['to'])->format('d/m/Y') ?: '—' }}
            @endif
        </div>
    </div>

    <div class="section-title">Indicadores</div>
    <div class="row">
        <div class="col-3">
            <div class="stat-card">
                <div class="stat-label">Registros</div>
                <div class="stat-value">{{ $summary['count']['value'] ?? '0' }}</div>
            </div>
        </div>
        <div class="col-3">
            <div class="stat-card">
                <div class="stat-label">Total neto</div>
                <div class="stat-value">{{ $summary['total']['value'] ?? 'Bs 0' }}</div>
            </div>
        </div>
        <div class="col-3">
            <div class="stat-card">
                <div class="stat-label">Costo total</div>
                <div class="stat-value">{{ $summary['cost']['value'] ?? 'Bs 0' }}</div>
            </div>
        </div>
        <div class="col-3">
            <div class="stat-card">
                <div class="stat-label">Ganancia</div>
                <div class="stat-value">{{ $summary['profit']['value'] ?? 'Bs 0' }}</div>
            </div>
        </div>
    </div>

    <div class="section-title">Detalle</div>
    <table>
        <thead>
            <tr>
                <th class="nowrap">Fecha</th>
                <th>Usuario</th>
                <th>Producto</th>
                <th>Promoción</th>
                <th class="nowrap right">m²</th>
                <th class="nowrap right">Precio/m²</th>
                <th class="nowrap right">Subtotal</th>
                <th class="nowrap right">Desc.</th>
                <th class="nowrap right">Total</th>
                <th class="nowrap right">Costo</th>
                <th class="nowrap right">Ganancia</th>
            </tr>
        </thead>
        <tbody>
            @forelse($rows as $row)
                <tr>
                    <td class="nowrap">{{ $row['fecha'] ?? '—' }}</td>
                    <td>{{ $row['usuario'] ?? '—' }}</td>
                    <td>{{ $row['producto'] ?? '—' }}</td>
                    <td>{{ $row['promocion'] ?? '—' }}</td>
                    <td class="right nowrap">
                        {{ $row['area_m2'] !== null ? number_format((float)$row['area_m2'], 2, ',', '.') : '—' }}
                    </td>
                    <td class="right nowrap">
                        {{ $row['precio_m2'] !== null ? ('Bs ' . number_format((float)$row['precio_m2'], 0, ',', '.')) : '—' }}
                    </td>
                    <td class="right nowrap">
                        {{ $row['subtotal'] !== null ? ('Bs ' . number_format((float)$row['subtotal'], 0, ',', '.')) : '—' }}
                    </td>
                    <td class="right nowrap">
                        @if($row['descuento_pct'] !== null)
                            {{ number_format((float)$row['descuento_pct'], 2, ',', '.') }}%
                            @if($row['descuento_monto'] !== null)
                                <div class="muted">Bs {{ number_format((float)$row['descuento_monto'], 0, ',', '.') }}</div>
                            @endif
                        @else
                            —
                        @endif
                    </td>
                    <td class="right nowrap">
                        {{ $row['total'] !== null ? ('Bs ' . number_format((float)$row['total'], 0, ',', '.')) : '—' }}
                    </td>
                    <td class="right nowrap">
                        {{ $row['costo_total'] !== null ? ('Bs ' . number_format((float)$row['costo_total'], 0, ',', '.')) : '—' }}
                    </td>
                    <td class="right nowrap">
                        {{ $row['ganancia'] !== null ? ('Bs ' . number_format((float)$row['ganancia'], 0, ',', '.')) : '—' }}
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="11" class="muted">Sin registros en el periodo seleccionado.</td>
                </tr>
            @endforelse
        </tbody>
    </table>

    <div class="footer">
        Reporte interno (solo admin). Los valores dependen de Costo_M2 configurado en Productos.
    </div>
</body>
</html>
