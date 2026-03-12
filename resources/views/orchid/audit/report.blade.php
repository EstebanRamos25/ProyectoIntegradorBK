<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Reporte de auditoría</title>
    <style>
        @page {
            margin: 24px 22px;
        }

        * {
            box-sizing: border-box;
        }

        body {
            font-family: DejaVu Sans, sans-serif;
            color: #1f2937;
            font-size: 11px;
            line-height: 1.45;
            margin: 0;
            background: #f8fafc;
        }

        .hero {
            background: #0f172a;
            color: #ffffff;
            border-radius: 18px;
            padding: 24px 26px;
            margin-bottom: 18px;
            border: 1px solid #1e3a8a;
        }

        .eyebrow {
            font-size: 10px;
            letter-spacing: 1.6px;
            text-transform: uppercase;
            color: #bfdbfe;
            margin-bottom: 8px;
        }

        h1 {
            margin: 0 0 6px;
            font-size: 28px;
            line-height: 1.1;
            color: #ffffff;
        }

        .subtitle {
            margin: 0;
            font-size: 12px;
            color: #e2e8f0;
        }

        .meta {
            margin-top: 16px;
            font-size: 10px;
            color: #cbd5e1;
        }

        .section-title {
            font-size: 15px;
            font-weight: bold;
            color: #0f172a;
            margin: 18px 0 10px;
        }

        .row {
            width: 100%;
            margin: 0 -1%;
        }

        .col-4,
        .col-6 {
            display: inline-block;
            vertical-align: top;
            margin: 0 1% 10px;
        }

        .col-4 {
            width: 31.3%;
        }

        .col-6 {
            width: 48%;
        }

        .stat-card,
        .panel,
        .activity-card {
            background: #ffffff;
            border-radius: 14px;
            border: 1px solid #e5e7eb;
        }

        .stat-card {
            padding: 14px 16px;
            min-height: 84px;
        }

        .stat-label {
            font-size: 10px;
            text-transform: uppercase;
            letter-spacing: 1px;
            color: #64748b;
            margin-bottom: 8px;
        }

        .stat-value {
            font-size: 24px;
            font-weight: bold;
            color: #0f172a;
        }

        .panel {
            padding: 14px 16px;
        }

        .panel h3 {
            margin: 0 0 10px;
            font-size: 12px;
            color: #0f172a;
        }

        .list {
            margin: 0;
            padding: 0;
            list-style: none;
        }

        .list li {
            border-bottom: 1px solid #eef2f7;
            padding: 8px 0;
        }

        .list li:last-child {
            border-bottom: 0;
            padding-bottom: 0;
        }

        .item-name {
            color: #0f172a;
            font-weight: 600;
        }

        .item-count {
            float: right;
            color: #1d4ed8;
            font-weight: bold;
        }

        .activity-card {
            padding: 16px;
            margin-bottom: 12px;
            page-break-inside: avoid;
        }

        .activity-header {
            margin-bottom: 10px;
        }

        .activity-title {
            font-size: 14px;
            font-weight: bold;
            color: #0f172a;
            margin: 0 0 4px;
        }

        .activity-subtitle {
            font-size: 10px;
            color: #64748b;
        }

        .badge {
            display: inline-block;
            padding: 4px 9px;
            border-radius: 999px;
            font-size: 9px;
            font-weight: bold;
            text-transform: uppercase;
            letter-spacing: .7px;
        }

        .badge.created {
            background: #dcfce7;
            color: #166534;
        }

        .badge.updated {
            background: #dbeafe;
            color: #1d4ed8;
        }

        .badge.deleted {
            background: #fee2e2;
            color: #b91c1c;
        }

        .badge.restored {
            background: #ede9fe;
            color: #6d28d9;
        }

        .badge.default {
            background: #e2e8f0;
            color: #334155;
        }

        .muted {
            color: #64748b;
        }

        .chips {
            margin: 12px 0 4px;
        }

        .chip {
            display: inline-block;
            margin: 0 6px 6px 0;
            padding: 5px 9px;
            font-size: 9px;
            border-radius: 999px;
            background: #eff6ff;
            color: #1e3a8a;
        }

        .changes-title {
            font-size: 11px;
            font-weight: bold;
            color: #0f172a;
            margin: 12px 0 8px;
        }

        .change-row {
            border: 1px solid #e5e7eb;
            border-radius: 12px;
            padding: 10px;
            margin-bottom: 8px;
            background: #f8fafc;
        }

        .change-field {
            font-weight: bold;
            color: #0f172a;
            margin-bottom: 8px;
        }

        .change-col {
            display: inline-block;
            width: 48.5%;
            vertical-align: top;
        }

        .change-col + .change-col {
            margin-left: 2%;
        }

        .change-label {
            font-size: 9px;
            text-transform: uppercase;
            letter-spacing: .8px;
            color: #64748b;
            margin-bottom: 5px;
        }

        .change-box {
            min-height: 44px;
            padding: 8px;
            border-radius: 8px;
            white-space: pre-wrap;
            word-break: break-word;
            border: 1px solid #e5e7eb;
            background: #ffffff;
        }

        .empty {
            padding: 24px;
            text-align: center;
            border: 1px dashed #cbd5e1;
            border-radius: 14px;
            background: #ffffff;
            color: #64748b;
        }

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
        <h1>Reporte de auditoría</h1>
        <p class="subtitle">Resumen ejecutivo y detalle visual de los eventos registrados en el sistema.</p>
        <div class="meta">
            Generado: {{ $generatedAt->format('d/m/Y H:i:s') }}
            @if(($summary['range']['from'] ?? null) && ($summary['range']['to'] ?? null))
                &nbsp;|&nbsp; Periodo cubierto: {{ $summary['range']['from']->format('d/m/Y H:i') }} - {{ $summary['range']['to']->format('d/m/Y H:i') }}
            @endif
        </div>
    </div>

    <div class="section-title">Indicadores principales</div>
    <div class="row">
        <div class="col-4">
            <div class="stat-card">
                <div class="stat-label">Registros auditados</div>
                <div class="stat-value">{{ number_format($summary['total'] ?? 0, 0, ',', '.') }}</div>
            </div>
        </div>
        <div class="col-4">
            <div class="stat-card">
                <div class="stat-label">Usuarios con actividad</div>
                <div class="stat-value">{{ number_format($summary['uniqueUsers'] ?? 0, 0, ',', '.') }}</div>
            </div>
        </div>
        <div class="col-4">
            <div class="stat-card">
                <div class="stat-label">Modelos impactados</div>
                <div class="stat-value">{{ number_format($summary['uniqueModels'] ?? 0, 0, ',', '.') }}</div>
            </div>
        </div>
    </div>

    <div class="row">
        <div class="col-6">
            <div class="panel">
                <h3>Eventos registrados</h3>
                <ul class="list">
                    @forelse(($summary['byEvent'] ?? []) as $event => $count)
                        <li>
                            <span class="item-name">{{ $event }}</span>
                            <span class="item-count">{{ $count }}</span>
                        </li>
                    @empty
                        <li>Sin eventos registrados.</li>
                    @endforelse
                </ul>
            </div>
        </div>
        <div class="col-6">
            <div class="panel" style="margin-bottom: 10px;">
                <h3>Usuarios con más movimientos</h3>
                <ul class="list">
                    @forelse(($summary['topUsers'] ?? []) as $user => $count)
                        <li>
                            <span class="item-name">{{ $user }}</span>
                            <span class="item-count">{{ $count }}</span>
                        </li>
                    @empty
                        <li>Sin actividad de usuarios.</li>
                    @endforelse
                </ul>
            </div>
            <div class="panel">
                <h3>Modelos más auditados</h3>
                <ul class="list">
                    @forelse(($summary['topModels'] ?? []) as $model => $count)
                        <li>
                            <span class="item-name">{{ $model }}</span>
                            <span class="item-count">{{ $count }}</span>
                        </li>
                    @empty
                        <li>Sin modelos auditados.</li>
                    @endforelse
                </ul>
            </div>
        </div>
    </div>

    <div class="section-title">Detalle de eventos</div>

    @forelse($activities as $activity)
        <div class="activity-card">
            <div class="activity-header">
                <span class="badge {{ $activity['event_key'] ?: 'default' }}">{{ $activity['event_label'] }}</span>
                <div class="activity-title" style="margin-top: 8px;">{{ $activity['description'] ?: 'Evento sin descripción' }}</div>
                <div class="activity-subtitle">
                    Registro #{{ $activity['id'] }} · {{ $activity['created_at'] }} · Usuario: {{ $activity['user'] }}
                </div>
            </div>

            <div class="chips">
                <span class="chip">Log: {{ $activity['log_name'] ?: 'default' }}</span>
                <span class="chip">Modelo: {{ $activity['model'] }}</span>
                <span class="chip">ID modelo: {{ $activity['subject_id'] }}</span>
            </div>

            @if(!empty($activity['changes']))
                <div class="changes-title">Cambios detectados</div>
                @foreach($activity['changes'] as $change)
                    <div class="change-row">
                        <div class="change-field">{{ $change['field'] }}</div>
                        <div class="change-col">
                            <div class="change-label">Antes</div>
                            <div class="change-box">{{ $change['before'] }}</div>
                        </div>
                        <div class="change-col">
                            <div class="change-label">Después</div>
                            <div class="change-box">{{ $change['after'] }}</div>
                        </div>
                    </div>
                @endforeach
            @else
                <div class="muted">Este evento no contiene diferencias de atributos registradas.</div>
            @endif
        </div>
    @empty
        <div class="empty">No hay registros de auditoría disponibles para generar el reporte.</div>
    @endforelse

    <div class="footer">
        Reporte generado automáticamente desde el módulo de auditoría.
    </div>
</body>
</html>
