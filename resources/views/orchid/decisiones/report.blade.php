<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Reporte de Decisiones y Tendencias (ML)</title>
    <style>
        body { font-family: sans-serif; font-size: 12px; color: #333; margin: 20px; }
        h1 { color: #1e293b; border-bottom: 2px solid #e2e8f0; padding-bottom: 10px; }
        .header-meta { margin-bottom: 30px; font-size: 13px; color: #64748b; }
        table { width: 100%; border-collapse: collapse; margin-top: 20px; }
        th, td { border: 1px solid #cbd5e1; padding: 10px; text-align: left; }
        th { background-color: #f1f5f9; color: #334155; font-weight: bold; }
        .badge { padding: 4px 8px; border-radius: 12px; font-weight: bold; font-size: 10px; text-transform: uppercase; }
        .badge-estrella { background-color: #f0fdf4; color: #16a34a; }
        .badge-riesgo { background-color: #fef2f2; color: #ef4444; }
        .badge-exceso { background-color: #fffbeb; color: #d97706; }
        .badge-neutro { background-color: #f1f5f9; color: #64748b; }
        .text-right { text-align: right; }
    </style>
</head>
<body>

    <h1>Decisiones y Tendencias (IA)</h1>
    <div class="header-meta">
        <strong>Generado el:</strong> {{ $generatedAt->format('d/m/Y H:i:s') }} <br>
        <strong>Reporte Inteligente:</strong> Cruce de Demanda (Score Red Neuronal) vs Inventario (Oferta).
    </div>

    <table>
        <thead>
            <tr>
                <th>Producto</th>
                <th class="text-right">Score (Demanda)</th>
                <th class="text-right">Cajas Stock</th>
                <th>Diagnóstico</th>
                <th>Decisión Sugerida</th>
            </tr>
        </thead>
        <tbody>
            @foreach($rows as $row)
                <tr>
                    <td>
                        <strong>{{ $row->nombre }}</strong><br>
                        <span style="color:#64748b; font-size:10px;">{{ $row->categoria }}</span>
                    </td>
                    <td class="text-right" style="color:#4f46e5; font-weight:bold;">
                        {{ $row->score }}
                    </td>
                    <td class="text-right">
                        {{ $row->stock }}
                    </td>
                    <td>
                        @if($row->decision === 'Estrella')
                            <span class="badge badge-estrella">Estrella</span>
                        @elseif($row->decision === 'Riesgo de Quiebre')
                            <span class="badge badge-riesgo">Riesgo Quiebre</span>
                        @elseif($row->decision === 'Exceso (Estancado)')
                            <span class="badge badge-exceso">Exceso</span>
                        @else
                            <span class="badge badge-neutro">{{ $row->decision }}</span>
                        @endif
                    </td>
                    <td>{{ $row->accion }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>

</body>
</html>
