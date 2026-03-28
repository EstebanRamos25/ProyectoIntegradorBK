<?php

namespace App\Services\Chatbot;

use App\Models\Inventario;
use App\Models\Categoria;
use App\Models\Escena;
use App\Models\Promocion;
use App\Models\Producto;
use App\Models\Proyecto;
use App\Models\User;
use App\Models\Venta;
use Carbon\Carbon;
use Illuminate\Support\Str;

class DbInsightsService
{
    /**
     * Ejecuta consultas seguras (whitelist) por módulo.
     * NO genera SQL con IA; solo heurísticas simples + Query Builder.
     */
    public function answer(string $message, ?string $module): ?string
    {
        $module = $this->normalizeModule($module);
        $text = Str::lower($message);

        // Respuestas instantáneas para saludos / cortesía (evita latencia del modelo)
        if ($this->isGreetingOnly($text)) {
            return "¡Hola! Soy CERABOT.\n\n" .
                "Puedo ayudarte con dudas del sistema (productos, inventarios, proyectos, escenas, ventas, usuarios).\n" .
                "Dime qué necesitas o en qué módulo estás trabajando.";
        }

        // Respuesta instantánea para "ayuda" / "qué puedes hacer" (evita latencia del modelo)
        if ($this->isHelpRequest($text)) {
            return $this->helpForModule($module);
        }

        // Vista general del módulo (lenguaje natural): "¿qué tengo/hay en este módulo?"
        // Importante: responde desde BD cuando aplica, para mantenerlo rápido y completo.
        if ($module !== '' && $this->isModuleOverviewRequest($text)) {
            return $this->overviewForModule($module);
        }

        // Resumen global cuando no hay módulo o cuando lo piden explícitamente
        if ($module === '' && $this->wantsSummary($text)) {
            return $this->summaryGlobal();
        }

        if ($module === 'productos') {
            return $this->answerProductos($text);
        }

        if ($module === 'inventarios') {
            return $this->answerInventarios($text);
        }

        if ($module === 'proyectos') {
            return $this->answerProyectos($text);
        }

        if ($module === 'escenas') {
            return $this->answerEscenas($text);
        }

        if ($module === 'ventas') {
            return $this->answerVentas($text);
        }

        if ($module === 'categorias') {
            return $this->answerCategorias($text);
        }

        if ($module === 'promociones') {
            return $this->answerPromociones($text);
        }

        if ($module === 'usuarios') {
            return $this->answerUsuarios($text);
        }

        return null;
    }

    private function isModuleOverviewRequest(string $text): bool
    {
        return Str::contains($text, [
            'que tengo en este modulo',
            'qué tengo en este modulo',
            'que hay en este modulo',
            'qué hay en este modulo',
            'que hay en el modulo',
            'qué hay en el modulo',
            'que hay aqui',
            'qué hay aquí',
            'que puedo ver',
            'qué puedo ver',
            'que se hace en este modulo',
            'qué se hace en este modulo',
        ]);
    }

    private function overviewForModule(string $module): string
    {
        if ($module === 'productos') {
            $total = Producto::query()->count();
            $categories = Categoria::query()->count();
            $invRows = Inventario::query()->count();
            $units = (int) Inventario::query()->sum('Cantidad');

            return "Estás en el módulo de PRODUCTOS. Resumen rápido:\n" .
                "- Productos registrados: {$total}\n" .
                "- Categorías: {$categories}\n" .
                "- Inventario: {$units} existencias (en {$invRows} registros)\n\n" .
                "Puedes pedirme: \"resumen de productos\", \"top 5 productos con más inventario\" o \"¿cuántas baldosas/cerámicos tenemos?\".";
        }

        if ($module === 'inventarios') {
            $rows = Inventario::query()->count();
            $units = (int) Inventario::query()->sum('Cantidad');
            $productsWithInv = Inventario::query()->whereNotNull('producto_id')->distinct('producto_id')->count('producto_id');

            return "Estás en el módulo de INVENTARIOS. Resumen rápido:\n" .
                "- Registros de inventario: {$rows}\n" .
                "- Existencias totales (suma Cantidad): {$units}\n" .
                "- Productos con inventario: {$productsWithInv}\n\n" .
                "Puedes pedirme: \"producto con más stock\" o \"top 5 productos por inventario\".";
        }

        if ($module === 'proyectos') {
            $total = Proyecto::query()->count();
            return "Estás en el módulo de PROYECTOS. Resumen rápido:\n" .
                "- Proyectos registrados: {$total}\n\n" .
                "Puedes pedirme: \"resumen de proyectos\" o \"proyectos recientes\".";
        }

        if ($module === 'escenas') {
            $total = Escena::query()->count();
            return "Estás en el módulo de ESCENAS. Resumen rápido:\n" .
                "- Escenas registradas: {$total}\n\n" .
                "Puedes pedirme: \"resumen de escenas\" o \"escenas recientes\".";
        }

        if ($module === 'ventas') {
            $total = Venta::query()->count();
            $salesTotal = (float) (Venta::query()->sum('Total') ?? 0);
            return "Estás en el módulo de VENTAS. Resumen rápido:\n" .
                "- Ventas registradas: {$total}\n" .
                "- Total vendido: " . number_format($salesTotal, 2) . "\n\n" .
                "Puedes pedirme: \"ventas de este mes\" o \"total vendido\".";
        }

        if ($module === 'categorias') {
            $total = Categoria::query()->count();
            return "Estás en el módulo de CATEGORÍAS. Resumen rápido:\n" .
                "- Categorías registradas: {$total}\n\n" .
                "Puedes pedirme: \"cuántas categorías hay\".";
        }

        if ($module === 'promociones') {
            $total = Promocion::query()->count();
            return "Estás en el módulo de PROMOCIONES. Resumen rápido:\n" .
                "- Promociones registradas: {$total}\n\n" .
                "Puedes pedirme: \"promociones recientes\".";
        }

        if ($module === 'usuarios') {
            $total = User::query()->count();
            return "Estás en el módulo de USUARIOS. Resumen rápido:\n" .
                "- Usuarios registrados: {$total}\n\n" .
                "Puedes pedirme: \"cuántos usuarios hay\".";
        }

        return "Estás en el módulo '{$module}'. Puedo darte un resumen si me dices: \"resumen\" o \"estadísticas\".";
    }

    private function isHelpRequest(string $text): bool
    {
        return Str::contains($text, [
            'que puedes hacer',
            'qué puedes hacer',
            'que haces',
            'qué haces',
            'ayuda',
            'help',
            'como funciona',
            'cómo funciona',
            'como usar',
            'cómo usar',
            'guia',
            'guía',
        ]);
    }

    private function helpForModule(string $module): string
    {
        $base = "Puedo ayudarte con el sistema CERABOL. Ejemplos de preguntas rápidas:\n";

        if ($module === 'productos') {
            return $base .
                "- \"¿Cuántos productos hay?\"\n" .
                "- \"Resumen de productos\"\n" .
                "- \"Top 5 productos con más inventario\"\n" .
                "- \"¿Cuántas baldosas/cerámicos tenemos?\"";
        }

        if ($module === 'inventarios') {
            return $base .
                "- \"Resumen de inventarios\"\n" .
                "- \"¿Cuántas unidades hay en total?\"\n" .
                "- \"¿Qué producto tiene más stock?\"";
        }

        if ($module === 'proyectos') {
            return $base .
                "- \"Resumen de proyectos\"\n" .
                "- \"¿Cuántos proyectos hay en total?\"\n" .
                "- \"Proyectos recientes\"";
        }

        if ($module === 'ventas') {
            return $base .
                "- \"Resumen de ventas\"\n" .
                "- \"Total vendido\"\n" .
                "- \"Ventas de este mes\"";
        }

        return $base .
            "- \"Resumen general\"\n" .
            "- \"¿Cuántos productos/proyectos/ventas hay?\"\n" .
            "Dime en qué módulo estás (productos, inventarios, proyectos, ventas, etc.) para darte respuestas más precisas.";
    }

    private function isGreetingOnly(string $text): bool
    {
        $t = trim($text);
        if ($t === '') return false;

        // Normalizar puntuación simple
        $t = str_replace([',', '.', '!', '¡', '?', '¿'], ' ', $t);
        $t = preg_replace('/\s+/', ' ', $t) ?: $t;
        $t = trim($t);

        // Casos comunes
        $greetings = [
            'hola',
            'buenas',
            'buenos dias',
            'buenas tardes',
            'buenas noches',
            'hey',
            'hello',
        ];

        if (in_array($t, $greetings, true)) return true;

        // Variantes cortas: "hola cerabot", "hola bot"
        if (Str::startsWith($t, 'hola ') && Str::length($t) <= 20) return true;

        return false;
    }

    private function normalizeModule(?string $module): string
    {
        $m = Str::lower(trim((string) $module));
        if ($m === '') return '';

        $aliases = [
            'producto' => 'productos',
            'productos' => 'productos',
            'inventario' => 'inventarios',
            'inventarios' => 'inventarios',
            'proyecto' => 'proyectos',
            'proyectos' => 'proyectos',
            'escena' => 'escenas',
            'escenas' => 'escenas',
            'usuario' => 'usuarios',
            'usuarios' => 'usuarios',
            'role' => 'roles',
            'roles' => 'roles',
            'categoria' => 'categorias',
            'categorias' => 'categorias',
            'promocion' => 'promociones',
            'promociones' => 'promociones',
            'venta' => 'ventas',
            'ventas' => 'ventas',
        ];

        return $aliases[$m] ?? $m;
    }

    private function wantsCount(string $text): bool
    {
        return Str::contains($text, [
            'cuántos',
            'cuantos',
            'cuántas',
            'cuantas',
            'cuánto',
            'cuanto',
            'cuánta',
            'cuanta',
            'cantidad',
            'total',
            'en total',
            'cuenta',
        ]);
    }

    private function wantsRegistered(string $text): bool
    {
        return Str::contains($text, ['registr', 'cread', 'alta', 'nuev']);
    }

    private function wantsSummary(string $text): bool
    {
        return Str::contains($text, ['resumen', 'resúmen', 'estad', 'cómo vamos', 'como vamos', 'dashboard', 'reporte']);
    }

    private function wantsRecent(string $text): bool
    {
        return Str::contains($text, ['hoy', 'esta semana', 'esta mes', 'este mes', 'últimos', 'ultimos', 'reciente', '30', '7']);
    }

    private function wantsMost(string $text): bool
    {
        return Str::contains($text, ['más', 'mas', 'mayor', 'maximo', 'máximo', 'top']);
    }

    private function wantsWhich(string $text): bool
    {
        return Str::contains($text, ['cual', 'cuál', 'que', 'qué']);
    }

    private function mentionsInventoryConcept(string $text): bool
    {
        return Str::contains($text, ['inventario', 'stock', 'cantidad', 'unidades', 'existenc', 'disponible']);
    }

    private function sinceDateForText(string $text): ?Carbon
    {
        $now = Carbon::now();

        if (Str::contains($text, ['hoy'])) return $now->copy()->startOfDay();
        if (Str::contains($text, ['esta semana'])) return $now->copy()->startOfWeek();
        if (Str::contains($text, ['este mes', 'esta mes'])) return $now->copy()->startOfMonth();
        if (Str::contains($text, ['30', 'últimos 30', 'ultimos 30'])) return $now->copy()->subDays(30);
        if (Str::contains($text, ['7', 'últimos 7', 'ultimos 7'])) return $now->copy()->subDays(7);

        return null;
    }

    private function summaryGlobal(): string
    {
        $users = User::query()->count();
        $products = Producto::query()->count();
        $projects = Proyecto::query()->count();
        $scenes = Escena::query()->count();
        $sales = Venta::query()->count();

        $salesTotal = (float) (Venta::query()->sum('Total') ?? 0);

        return "Resumen general del sistema:\n" .
            "- Usuarios: {$users}\n" .
            "- Productos: {$products}\n" .
            "- Proyectos: {$projects}\n" .
            "- Escenas: {$scenes}\n" .
            "- Ventas registradas: {$sales}\n" .
            "- Total vendido (suma de ventas): " . number_format($salesTotal, 2) . "\n" .
            "Si quieres, dime el módulo y te doy el detalle (ej: 'resumen de ventas').";
    }

    private function answerProductos(string $text): ?string
    {
        if ($this->wantsSummary($text)) {
            $total = Producto::query()->count();
            $categories = Categoria::query()->count();
            $invRows = Inventario::query()->count();
            $units = (int) Inventario::query()->sum('Cantidad');

            return "Resumen de productos:\n" .
                "- Productos registrados: {$total}\n" .
                "- Categorías: {$categories}\n" .
                "- Registros de inventario: {$invRows}\n" .
                "- Unidades totales en inventario: {$units}";
        }

        // Preguntas tipo: "¿Cuántas baldosas/azulejos cerámicos tenemos?"
        // Aunque no digan "producto", si estamos en el módulo productos asumimos que buscan un conteo/suma.
        // Nota: si también dicen "más/mas" (máximo), no entramos aquí para permitir la regla de "producto con mayor inventario".
        if ($this->wantsCount($text) && !$this->wantsMost($text)) {
            if ($this->mentionsCeramicTiles($text)) {
                return $this->answerCeramicTilesCount();
            }

            $total = Producto::query()->count();
            $invRows = Inventario::query()->count();
            $units = (int) Inventario::query()->sum('Cantidad');
            return "En productos hay {$total} registros. En inventario hay {$units} existencias (en {$invRows} registros).";
        }

        // 1) Total de productos
        if ($this->wantsCount($text) && !$this->wantsMost($text) && Str::contains($text, ['producto'])) {
            $total = Producto::query()->count();
            return "En total hay {$total} productos registrados.";
        }

        // 2) Producto con mayor inventario (suma de inventarios)
        // Soportar lenguaje natural tipo: "¿Cuál es el producto que más tenemos?"
        // En módulo productos, si preguntan por el "más" y mencionan producto (o inventario/cantidad), asumimos que buscan el mayor stock.
        if (
            $this->wantsMost($text)
            && (
                Str::contains($text, ['producto', 'productos'])
                || $this->mentionsInventoryConcept($text)
                || ($this->wantsWhich($text) && Str::contains($text, ['tenemos', 'hay']))
            )
        ) {
            $row = Inventario::query()
                ->selectRaw('producto_id, SUM(COALESCE(Cantidad,0)) as total')
                ->groupBy('producto_id')
                ->orderByDesc('total')
                ->with('producto:id,Nombre')
                ->first();

            if (!$row || !$row->producto) {
                return 'Aún no hay inventario asociado a productos para calcular el mayor stock.';
            }

            $name = $row->producto->Nombre;
            $total = (int) $row->total;
            return "El producto con mayor cantidad en inventario es '{$name}' con {$total} existencias (suma de inventarios).";
        }

        // 3) Top 5 por inventario
        if (Str::contains($text, ['top 5', 'top5', 'top cinco', '5']) && Str::contains($text, ['producto', 'stock', 'inventario'])) {
            $rows = Inventario::query()
                ->selectRaw('producto_id, SUM(COALESCE(Cantidad,0)) as total')
                ->groupBy('producto_id')
                ->orderByDesc('total')
                ->with('producto:id,Nombre')
                ->limit(5)
                ->get();

            if ($rows->isEmpty()) {
                return 'No hay datos de inventario para armar un top de productos.';
            }

            $lines = $rows->map(function ($r, $idx) {
                $n = $idx + 1;
                $name = $r->producto?->Nombre ?? ('ID ' . $r->producto_id);
                return "{$n}. {$name}: " . (int) $r->total;
            })->implode("\n");

            return "Top 5 productos por inventario (suma de inventarios):\n{$lines}";
        }

        return null;
    }

    private function mentionsCeramicTiles(string $text): bool
    {
        return Str::contains($text, [
            'baldos',
            'azulej',
            'ceramic',
            'cerámic',
            'porcelanat',
            'gres',
        ]);
    }

    private function answerCeramicTilesCount(): string
    {
        $keywords = ['%baldos%', '%azulej%', '%ceramic%', '%porcelanat%', '%gres%'];

        $productMatches = Producto::query()
            ->where(function ($q) use ($keywords) {
                foreach ($keywords as $kw) {
                    $q->orWhere('Nombre', 'like', $kw)
                        ->orWhere('Descripcion', 'like', $kw);
                }
            });

        $productsCount = (int) $productMatches->count();

        // Sumar inventario asociado a esos productos
        $units = (int) Inventario::query()
            ->whereHas('producto', function ($q) use ($keywords) {
                $q->where(function ($qq) use ($keywords) {
                    foreach ($keywords as $kw) {
                        $qq->orWhere('Nombre', 'like', $kw)
                            ->orWhere('Descripcion', 'like', $kw);
                    }
                });
            })
            ->sum('Cantidad');

        if ($productsCount === 0) {
            return "No encontré productos que parezcan baldosas/azulejos cerámicos por nombre o descripción. Si me dices el nombre exacto (o una palabra clave del producto), lo busco mejor.";
        }

        return "Baldosas/cerámicos detectados (por nombre/descr.): {$productsCount} productos. Existencias totales en inventario para esos productos: {$units}.";
    }

    private function answerInventarios(string $text): ?string
    {
        if ($this->wantsSummary($text)) {
            $rows = Inventario::query()->count();
            $sum = (int) Inventario::query()->sum('Cantidad');
            $productsWithInv = Inventario::query()->whereNotNull('producto_id')->distinct('producto_id')->count('producto_id');
            return "Resumen de inventarios:\n" .
                "- Registros: {$rows}\n" .
                "- Existencias totales (suma Cantidad): {$sum}\n" .
                "- Productos con inventario: {$productsWithInv}";
        }

        // Total de registros de inventario
        if ($this->wantsCount($text) && Str::contains($text, ['inventario'])) {
            $total = Inventario::query()->count();
            return "En total hay {$total} registros de inventario.";
        }

        // Total de unidades (suma Cantidad)
        if ($this->wantsCount($text) && Str::contains($text, ['unidades', 'cantidad', 'stock'])) {
            $sum = (int) Inventario::query()->sum('Cantidad');
            return "La suma total de existencias (Cantidad) en inventario es {$sum}.";
        }

        return null;
    }

    private function answerProyectos(string $text): ?string
    {
        $since = $this->sinceDateForText($text);

        if ($this->wantsSummary($text)) {
            $total = Proyecto::query()->count();
            $recent30 = Proyecto::query()->where('created_at', '>=', Carbon::now()->subDays(30))->count();
            $topType = Proyecto::query()
                ->selectRaw('Tipo_Proyecto as tipo, COUNT(*) as total')
                ->whereNotNull('Tipo_Proyecto')
                ->groupBy('Tipo_Proyecto')
                ->orderByDesc('total')
                ->first();

            $typeLine = $topType ? ("- Tipo más frecuente: {$topType->tipo} ({$topType->total})") : '- Tipo más frecuente: (sin datos)';

            return "Resumen de proyectos:\n" .
                "- Proyectos registrados: {$total}\n" .
                "- Proyectos últimos 30 días: {$recent30}\n" .
                $typeLine;
        }

        if ($this->wantsCount($text) && Str::contains($text, ['proyecto'])) {
            if ($since && $this->wantsRegistered($text)) {
                $count = Proyecto::query()->where('created_at', '>=', $since)->count();
                return "Proyectos registrados desde {$since->toDateString()}: {$count}.";
            }

            $count = Proyecto::query()->count();
            return "En total hay {$count} proyectos registrados.";
        }

        if ($this->wantsMost($text) && Str::contains($text, ['tipo', 'tipo_proyecto', 'tipo proyecto'])) {
            $row = Proyecto::query()
                ->selectRaw('Tipo_Proyecto as tipo, COUNT(*) as total')
                ->whereNotNull('Tipo_Proyecto')
                ->groupBy('Tipo_Proyecto')
                ->orderByDesc('total')
                ->first();

            if (!$row) return 'No hay tipos de proyecto suficientes para calcular cuál hay más.';

            return "El tipo de proyecto más frecuente es '{$row->tipo}' con {$row->total} registros.";
        }

        return null;
    }

    private function answerEscenas(string $text): ?string
    {
        $since = $this->sinceDateForText($text);

        if ($this->wantsSummary($text)) {
            $total = Escena::query()->count();
            $recent30 = Escena::query()->where('created_at', '>=', Carbon::now()->subDays(30))->count();
            $topType = Escena::query()
                ->selectRaw('Tipo_Diseño as tipo, COUNT(*) as total')
                ->whereNotNull('Tipo_Diseño')
                ->groupBy('Tipo_Diseño')
                ->orderByDesc('total')
                ->first();
            $typeLine = $topType ? ("- Tipo de diseño más frecuente: {$topType->tipo} ({$topType->total})") : '- Tipo de diseño más frecuente: (sin datos)';

            return "Resumen de escenas:\n" .
                "- Escenas registradas: {$total}\n" .
                "- Escenas últimos 30 días: {$recent30}\n" .
                $typeLine;
        }

        if ($this->wantsCount($text) && Str::contains($text, ['escena'])) {
            if ($since && $this->wantsRegistered($text)) {
                $count = Escena::query()->where('created_at', '>=', $since)->count();
                return "Escenas registradas desde {$since->toDateString()}: {$count}.";
            }
            $count = Escena::query()->count();
            return "En total hay {$count} escenas registradas.";
        }

        if ($this->wantsMost($text) && Str::contains($text, ['tipo', 'diseño', 'diseno', 'tipo_dise'])) {
            $row = Escena::query()
                ->selectRaw('Tipo_Diseño as tipo, COUNT(*) as total')
                ->whereNotNull('Tipo_Diseño')
                ->groupBy('Tipo_Diseño')
                ->orderByDesc('total')
                ->first();
            if (!$row) return 'No hay tipos de diseño suficientes para calcular cuál hay más.';
            return "El tipo de diseño más frecuente es '{$row->tipo}' con {$row->total} registros.";
        }

        return null;
    }

    private function answerVentas(string $text): ?string
    {
        $since = $this->sinceDateForText($text);

        if ($this->wantsSummary($text)) {
            $total = Venta::query()->count();
            $sum = (float) (Venta::query()->sum('Total') ?? 0);
            $avg = (float) (Venta::query()->avg('Total') ?? 0);
            $recent30 = Venta::query()->where('created_at', '>=', Carbon::now()->subDays(30))->count();
            $sum30 = (float) (Venta::query()->where('created_at', '>=', Carbon::now()->subDays(30))->sum('Total') ?? 0);

            return "Resumen de ventas:\n" .
                "- Ventas registradas: {$total}\n" .
                "- Total vendido: " . number_format($sum, 2) . "\n" .
                "- Promedio por venta: " . number_format($avg, 2) . "\n" .
                "- Ventas últimos 30 días: {$recent30}\n" .
                "- Total vendido últimos 30 días: " . number_format($sum30, 2);
        }

        if ($this->wantsCount($text) && Str::contains($text, ['venta'])) {
            if ($since && $this->wantsRegistered($text)) {
                $count = Venta::query()->where('created_at', '>=', $since)->count();
                return "Ventas registradas desde {$since->toDateString()}: {$count}.";
            }
            $count = Venta::query()->count();
            return "En total hay {$count} ventas registradas.";
        }

        if ($this->wantsMost($text) && Str::contains($text, ['usuario', 'cliente', 'comprador'])) {
            $row = Venta::query()
                ->selectRaw('usuario_id, COUNT(*) as total')
                ->whereNotNull('usuario_id')
                ->groupBy('usuario_id')
                ->orderByDesc('total')
                ->with('usuario:id,name')
                ->first();
            if (!$row || !$row->usuario) return 'No hay usuarios suficientes en ventas para calcular quién tiene más.';
            return "El usuario con más compras es '{$row->usuario->name}' con {$row->total} ventas.";
        }

        if ($this->wantsMost($text) && Str::contains($text, ['promocion', 'promoción', 'descuento'])) {
            $row = Venta::query()
                ->selectRaw('promocion_id, COUNT(*) as total')
                ->whereNotNull('promocion_id')
                ->groupBy('promocion_id')
                ->orderByDesc('total')
                ->with('promocion:id,Nombre')
                ->first();
            if (!$row || !$row->promocion) return 'No hay promociones suficientes en ventas para calcular cuál se usa más.';
            return "La promoción más usada es '{$row->promocion->Nombre}' con {$row->total} ventas.";
        }

        return null;
    }

    private function answerCategorias(string $text): ?string
    {
        if ($this->wantsSummary($text)) {
            $total = Categoria::query()->count();
            $withProducts = Categoria::query()->has('productos')->count();
            $top = Categoria::query()
                ->withCount('productos')
                ->orderByDesc('productos_count')
                ->first();

            $topLine = $top ? ("- Categoría con más productos: {$top->Nombre} ({$top->productos_count})") : '- Categoría con más productos: (sin datos)';

            return "Resumen de categorías:\n" .
                "- Categorías registradas: {$total}\n" .
                "- Categorías con al menos 1 producto: {$withProducts}\n" .
                $topLine;
        }

        if ($this->wantsCount($text) && Str::contains($text, ['categor'])) {
            $count = Categoria::query()->count();
            return "En total hay {$count} categorías registradas.";
        }

        if ($this->wantsMost($text) && Str::contains($text, ['producto'])) {
            $top = Categoria::query()->withCount('productos')->orderByDesc('productos_count')->first();
            if (!$top) return 'No hay categorías suficientes para calcular cuál tiene más productos.';
            return "La categoría con más productos es '{$top->Nombre}' con {$top->productos_count} productos.";
        }

        return null;
    }

    private function answerPromociones(string $text): ?string
    {
        if ($this->wantsSummary($text)) {
            $total = Promocion::query()->count();
            $avg = (float) (Promocion::query()->avg('Descuento') ?? 0);
            $max = (float) (Promocion::query()->max('Descuento') ?? 0);
            return "Resumen de promociones:\n" .
                "- Promociones registradas: {$total}\n" .
                "- Descuento promedio: " . number_format($avg, 2) . "\n" .
                "- Descuento máximo: " . number_format($max, 2);
        }

        if ($this->wantsCount($text) && Str::contains($text, ['promoc'])) {
            $count = Promocion::query()->count();
            return "En total hay {$count} promociones registradas.";
        }

        if ($this->wantsMost($text) && Str::contains($text, ['descuento'])) {
            $row = Promocion::query()->orderByDesc('Descuento')->first();
            if (!$row) return 'No hay promociones suficientes para calcular el mayor descuento.';
            return "La promoción con mayor descuento es '{$row->Nombre}' con " . number_format((float) $row->Descuento, 2) . ".";
        }

        return null;
    }

    private function answerUsuarios(string $text): ?string
    {
        $since = $this->sinceDateForText($text);

        if ($this->wantsSummary($text)) {
            $total = User::query()->count();
            $recent30 = User::query()->where('created_at', '>=', Carbon::now()->subDays(30))->count();
            return "Resumen de usuarios:\n" .
                "- Usuarios totales: {$total}\n" .
                "- Usuarios últimos 30 días: {$recent30}";
        }

        if ($this->wantsCount($text) && Str::contains($text, ['usuario', 'usuarios'])) {
            if ($since && $this->wantsRegistered($text)) {
                $count = User::query()->where('created_at', '>=', $since)->count();
                return "Usuarios registrados desde {$since->toDateString()}: {$count}.";
            }
            $count = User::query()->count();
            return "En total hay {$count} usuarios.";
        }

        return null;
    }
}
