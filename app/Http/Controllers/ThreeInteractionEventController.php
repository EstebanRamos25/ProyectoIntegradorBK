<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\Producto;
use App\Models\ThreeInteractionEvent;
use App\Models\ThreeScene;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class ThreeInteractionEventController extends Controller
{
    public function store(Request $request): JsonResponse
    {
        $userId = (int) (Auth::id() ?? 0);
        if ($userId <= 0) {
            return response()->json(['message' => 'Unauthorized'], 401);
        }

        $validated = $request->validate([
            'scene_id' => ['required', 'integer', 'min:1'],
            'event_type' => ['required', 'string', 'max:50'],
            'producto_id' => ['nullable', 'integer', 'min:1'],
            'product_id' => ['nullable', 'integer', 'min:1'],
            'value' => ['nullable', 'numeric'],
            'meta' => ['nullable', 'array'],
        ]);

        $sceneId = (int) $validated['scene_id'];
        $scene = ThreeScene::query()
            ->whereKey($sceneId)
            ->where('user_id', $userId)
            ->first();

        if (!$scene) {
            return response()->json(['message' => 'Escena no encontrada.'], 404);
        }

        $allowed = [
            'catalog_view',
            'material_select',
            'wall_material_select',
            'quote_generate',
            'recommendations_shown',
            'recommendation_click',
        ];

        $eventType = (string) $validated['event_type'];
        if (!in_array($eventType, $allowed, true)) {
            return response()->json(['message' => 'event_type inválido.'], 422);
        }

        $productoId = (int) ($validated['producto_id'] ?? $validated['product_id'] ?? 0);
        $categoriaId = null;

        if ($productoId > 0) {
            $producto = Producto::query()->find($productoId);
            if (!$producto) {
                return response()->json(['message' => 'Producto no encontrado.'], 422);
            }

            $categoriaId = $producto->categoria_id ? (int) $producto->categoria_id : null;
        } else {
            $productoId = null;
        }

        ThreeInteractionEvent::query()->create([
            'user_id' => $userId,
            'three_scene_id' => $sceneId,
            'producto_id' => $productoId,
            'categoria_id' => $categoriaId,
            'event_type' => $eventType,
            'value' => array_key_exists('value', $validated) ? (float) $validated['value'] : null,
            'meta' => $validated['meta'] ?? null,
        ]);

        return response()->json(['ok' => true]);
    }
}
