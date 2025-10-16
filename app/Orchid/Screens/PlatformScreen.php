<?php

declare(strict_types=1);

namespace App\Orchid\Screens;

use App\Models\Escena;
use App\Models\Inventario;
use App\Models\Producto;
use App\Models\Proyecto;
use App\Orchid\Layouts\Examples\ChartBarExample;
use App\Orchid\Layouts\Examples\ChartLineExample;
use App\Orchid\Layouts\Examples\ChartPieExample;
use App\Orchid\Layouts\Examples\ChartPercentageExample;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Orchid\Screen\Screen;
use Orchid\Screen\TD;
use Orchid\Support\Facades\Layout;

class PlatformScreen extends Screen
{
    /**
     * Fetch data to be displayed on the screen.
     *
     * @return array
     */
    public function query(): iterable
        {
            // Totals
            $totals = [
                'products'    => Producto::count(),
                'inventories' => Inventario::count(),
                'projects'    => Proyecto::count(),
                'scenes'      => Escena::count(),
            ];

            // Last 6 months
            $months = 6;
            $start = Carbon::now()->startOfMonth()->subMonths($months - 1);
            $labels = [];
            for ($i = 0; $i < $months; $i++) {
                $labels[] = $start->copy()->addMonths($i)->format('M y');
            }

            $productCountsByMonth = $this->countsByMonth(Producto::class, $start, $months);
            $projectCountsByMonth = $this->countsByMonth(Proyecto::class, $start, $months);

            // Recent Projects
            $recentProjects = Proyecto::with(['user', 'producto'])
                ->latest()
                ->take(8)
                ->get();

            // Products by Category (top 8)
            $prodCatAgg = DB::table('productos as p')
                ->join('categorias as c', 'c.id', '=', 'p.categoria_id')
                ->selectRaw('c.Nombre as label, COUNT(*) as total')
                ->groupBy('c.Nombre')
                ->orderByDesc('total')
                ->limit(8)
                ->get();
            $prodCatLabels = $prodCatAgg->pluck('label')->all();
            $prodCatValues = $prodCatAgg->pluck('total')->map(fn($v) => (int)$v)->all();

            // Inventories by State
            $invStateAgg = DB::table('inventarios')
                ->selectRaw('COALESCE(Estado, "Sin estado") as label, COUNT(*) as total')
                ->groupBy('label')
                ->orderByDesc('total')
                ->get();
            $invStateLabels = $invStateAgg->pluck('label')->all();
            $invStateValues = $invStateAgg->pluck('total')->map(fn($v) => (int)$v)->all();

            return [
                'metrics' => [
                    'products'    => ['value' => number_format($totals['products']), 'diff' => 0],
                    'inventories' => ['value' => number_format($totals['inventories']), 'diff' => 0],
                    'projects'    => ['value' => number_format($totals['projects']), 'diff' => 0],
                    'scenes'      => ['value' => number_format($totals['scenes']), 'diff' => 0],
                ],
                'charts'  => [
                    [
                        'name'   => 'Productos',
                        'values' => $productCountsByMonth,
                        'labels' => $labels,
                    ],
                    [
                        'name'   => 'Proyectos',
                        'values' => $projectCountsByMonth,
                        'labels' => $labels,
                    ],
                ],
                'recentProjects' => $recentProjects,
                'productsByCategory' => [
                    [
                        'name'   => 'Productos por categoria',
                        'values' => $prodCatValues,
                        'labels' => $prodCatLabels,
                    ],
                ],
                'inventoriesByState' => [
                    [
                        'name'   => 'Inventarios por estado',
                        'values' => $invStateValues,
                        'labels' => $invStateLabels,
                    ],
                ],
            ];
        }

        /**
         * Helper: generate monthly counts for a model in a fixed window.
         */
        private function countsByMonth(string $modelClass, Carbon $start, int $months): array
        {
            $map = $modelClass::query()
                ->select(DB::raw('DATE_FORMAT(created_at, "%Y-%m") as ym'), DB::raw('COUNT(*) as c'))
                ->where('created_at', '>=', $start->copy()->startOfMonth())
                ->groupBy('ym')
                ->orderBy('ym')
                ->pluck('c', 'ym')
                ->all();

            $values = [];
            for ($i = 0; $i < $months; $i++) {
                $key = $start->copy()->addMonths($i)->format('Y-m');
                $values[] = (int)($map[$key] ?? 0);
            }
            return $values;
        }

        /**
         * The name of the screen displayed in the header.
         */
        public function name(): ?string
        {
            return 'Dashboard';
        }

        /**
         * Display header description.
         */
        public function description(): ?string
        {
            return 'Resumen estadistico de Productos, Inventarios, Proyectos y Escenas.';
        }

        /**
         * The screen's action buttons.
         *
         * @return \Orchid\Screen\Action[]
         */
        public function commandBar(): iterable
        {
            return [];
        }

        /**
         * The screen's layout elements.
         *
         * @return string[]|\Orchid\Screen\Layout[]
         */
        public function layout(): iterable
        {
            return [
                Layout::metrics([
                    'Productos'    => 'metrics.products',
                    'Inventarios'  => 'metrics.inventories',
                    'Proyectos'    => 'metrics.projects',
                    'Escenas'      => 'metrics.scenes',
                ]),

                Layout::columns([
                    ChartLineExample::make('charts', 'Actividad (6 meses)')
                        ->description('Altas mensuales de Productos y Proyectos.'),
                    ChartBarExample::make('charts', 'Comparativo')
                        ->description('Comparacion mensual de Productos vs Proyectos.'),
                ]),

                Layout::table('recentProjects', [
                    TD::make('Nombre', 'Proyecto'),
                    TD::make('user.name', 'Usuario'),
                    TD::make('producto.Nombre', 'Producto'),
                    TD::make('created_at', 'Creado')->render(fn ($p) => optional($p->created_at)?->toDateTimeString()),
                ])->title('Proyectos recientes'),

                Layout::columns([
                    ChartPieExample::make('productsByCategory', 'Productos por categoria')
                        ->description('Distribucion de productos por categoria (top 8).'),
                    ChartPercentageExample::make('inventoriesByState', 'Inventarios por estado')
                        ->description('Porcentaje de inventarios por estado actual.'),
                ]),
            ];
        }
    }
