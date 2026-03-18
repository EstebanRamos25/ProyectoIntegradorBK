<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\Producto;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Str;

class ThreeMaterialsController extends Controller
{
    public function index(): JsonResponse
    {
        $productos = Producto::query()
            ->with(['categoria', 'attachment'])
            ->orderBy('id', 'desc')
            ->get();

        $items = $productos
            ->map(function (Producto $producto): ?array {
                $attachment = $producto->attachment('image')->first();
                $url = null;

                if ($attachment && is_object($attachment) && method_exists($attachment, 'url')) {
                    $url = $attachment->url();
                }

                if (empty($url)) {
                    return null;
                }

                $tipo = Str::lower((string) optional($producto->categoria)->Tipo_Material);
                $catNombre = Str::lower((string) optional($producto->categoria)->Nombre);
                $nombre = Str::lower((string) $producto->Nombre);

                $hayMadera = Str::contains($tipo, 'mader') || Str::contains($catNombre, 'mader') || Str::contains($nombre, 'mader');
                $hayCeramica = Str::contains($tipo, 'ceram') || Str::contains($tipo, 'porcel')
                    || Str::contains($catNombre, 'ceram') || Str::contains($catNombre, 'porcel')
                    || Str::contains($nombre, 'ceram') || Str::contains($nombre, 'porcel');

                $kind = $hayMadera ? 'plank' : ($hayCeramica ? 'tile' : 'tile');

                return [
                    'id' => (int) $producto->id,
                    'name' => (string) $producto->Nombre,
                    'kind' => $kind, // tile | plank (para compatibilidad con cotización)
                    'price_per_m2' => (float) ($producto->Precio ?? 0),
                    'image_url' => (string) $url,
                    'category' => [
                        'id' => (int) ($producto->categoria_id ?? 0),
                        'name' => (string) (optional($producto->categoria)->Nombre ?? ''),
                        'tipo_material' => (string) (optional($producto->categoria)->Tipo_Material ?? ''),
                    ],
                ];
            })
            ->filter()
            ->values();

        return response()->json([
            'items' => $items,
            'count' => $items->count(),
        ]);
    }
}
