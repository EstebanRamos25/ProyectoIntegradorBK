<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Reporte de inventarios</title>
    <style>
        @page { margin: 22px 20px; }

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
            padding: 22px 24px;
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
            font-size: 24px;
            line-height: 1.1;
            color: #ffffff;
        }

        .subtitle {
            margin: 0;
            font-size: 11px;
            color: #e2e8f0;
        }

        .meta {
            margin-top: 14px;
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

        .stat-card, .panel {
            background: #ffffff;
            border-radius: 14px;
            border: 1px solid #e5e7eb;
        }

        .stat-card { padding: 12px 14px; min-height: 72px; }

        .stat-label {
            font-size: 9px;
            text-transform: uppercase;
            letter-spacing: 1px;
            color: #64748b;
            margin-bottom: 8px;
        }

        .stat-value { font-size: 20px; font-weight: bold; color: #0f172a; }

        .panel { padding: 12px 14px; }
        .panel h3 { margin: 0 0 10px; font-size: 11px; color: #0f172a; }

        .list { margin: 0; padding: 0; list-style: none; }
        .list li { border-bottom: 1px solid #eef2f7; padding: 7px 0; }
        .list li:last-child { border-bottom: 0; padding-bottom: 0; }

        .item-name { color: #0f172a; font-weight: 600; }
        .item-count { float: right; color: #1d4ed8; font-weight: bold; }

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

        .footer {
            margin-top: 10px;
            text-align: center;
            color: #94a3b8;
            font-size: 9px;
        }

        .nowrap { white-space: nowrap; }
    </style>
</head>
<body>
    <div class="hero">
        <div class="eyebrow">Proyecto Integrador BK</div>
        <h1>Reporte de inventarios</h1>
        <p class="subtitle">Resumen de existencias por lote y producto (cajas disponibles / entrada).</p>
        <div class="meta">
            Generado: {{ $generatedAt->format('d/m/Y H:i:s') }}
            @if(($summary['range']['from'] ?? null) && ($summary['range']['to'] ?? null))
                &nbsp;|&nbsp; Periodo: {{ optional($summary['range']['from'])->format('d/m/Y H:i') }} - {{ optional($summary['range']['to'])->format('d/m/Y H:i') }}
            @endif
        </div>
    </div>

    <div class="section-title">Indicadores principales</div>
    <div class="row">
        <div class="col-3">
            <div class="stat-card">
                <div class="stat-label">Registros</div>
                <div class="stat-value">{{ number_format((int)($summary['totalRegistros'] ?? 0), 0, ',', '.') }}</div>
            </div>
        </div>
        <div class="col-3">
            <div class="stat-card">
                <div class="stat-label">Productos distintos</div>
                <div class="stat-value">{{ number_format((int)($summary['productosUnicos'] ?? 0), 0, ',', '.') }}</div>
            </div>
        </div>
        <div class="col-3">
            <div class="stat-card">
                <div class="stat-label">Cajas disponibles</div>
                <div class="stat-value">{{ number_format((int)($summary['totalCajasDisponibles'] ?? 0), 0, ',', '.') }}</div>
            </div>
        </div>
        <div class="col-3">
            <div class="stat-card">
                <div class="stat-label">Cajas de entrada</div>
                <div class="stat-value">{{ number_format((int)($summary['totalCajasEntrada'] ?? 0), 0, ',', '.') }}</div>
            </div>
        </div>
    </div>

    <div class="row">
        <div class="col-6">
            <div class="panel">
                <h3>Top productos por cajas disponibles</h3>
                <ul class="list">
                    @forelse(($topProducts ?? []) as $product => $count)
                        <li>
                            <span class="item-name">{{ $product }}</span>
                            <span class="item-count">{{ number_format((int)$count, 0, ',', '.') }}</span>
                        </li>
                    @empty
                        <li class="muted">Sin datos.</li>
                    @endforelse
                </ul>
            </div>
        </div>
        <div class="col-6">
            <div class="panel">
                <h3>Alertas</h3>
                <ul class="list">
                    <li>
                        <span class="item-name">Lotes sin disponibles</span>
                        <span class="item-count">{{ number_format((int)($summary['sinDisponibles'] ?? 0), 0, ',', '.') }}</span>
                    </li>
                    <li>
                        <span class="item-name">Cantidad (legacy) total</span>
                        <span class="item-count">{{ number_format((int)($summary['totalLegacyCantidad'] ?? 0), 0, ',', '.') }}</span>
                    </li>
                </ul>
                <div class="muted" style="margin-top:8px;">"Sin disponibles" considera Cajas_Disponibles y Cantidad (legacy) en cero.</div>
            </div>
        </div>
    </div>

    <div class="section-title">Detalle (lotes)</div>

    <table>
        <thead>
            <tr>
                <th class="nowrap">ID</th>
                <th>Producto</th>
                <th class="nowrap">Lote</th>
                <th class="nowrap">Tono</th>
                <th class="nowrap">Calibre</th>
                <th class="nowrap">Cajas disp.</th>
                <th class="nowrap">Cajas entr.</th>
                <th class="nowrap">Costo/m²</th>
                <th class="nowrap">Cant. legacy</th>
                <th>Ubicación</th>
                <th class="nowrap">Estado</th>
                <th class="nowrap">Fecha ingreso</th>
                <th class="nowrap">Actualizado</th>
            </tr>
        </thead>
        <tbody>
            @forelse(($rows ?? []) as $row)
                <tr>
                    <td class="nowrap">{{ $row['id'] }}</td>
                    <td>{{ $row['producto'] }}</td>
                    <td class="nowrap">{{ $row['lote'] }}</td>
                    <td class="nowrap">{{ $row['tono'] }}</td>
                    <td class="nowrap">{{ $row['calibre'] }}</td>
                    <td class="nowrap">{{ number_format((int)$row['cajas_disponibles'], 0, ',', '.') }}</td>
                    <td class="nowrap">{{ number_format((int)$row['cajas_entrada'], 0, ',', '.') }}</td>
                    <td class="nowrap">{{ $row['costo_m2'] !== null ? number_format((float)$row['costo_m2'], 2, ',', '.') : '—' }}</td>
                    <td class="nowrap">{{ number_format((int)$row['legacy_cantidad'], 0, ',', '.') }}</td>
                    <td>{{ $row['ubicacion'] }}</td>
                    <td class="nowrap">{{ $row['estado'] }}</td>
                    <td class="nowrap">{{ $row['fecha_ingreso'] }}</td>
                    <td class="nowrap">{{ $row['updated_at'] }}</td>
                </tr>
            @empty
                <tr>
                    <td colspan="12" class="muted" style="text-align:center;padding:14px;">No hay inventarios para generar el reporte.</td>
                </tr>
            @endforelse
        </tbody>
    </table>

    <div class="footer">Reporte generado automáticamente desde el módulo de Inventarios.</div>
</body>
</html>
