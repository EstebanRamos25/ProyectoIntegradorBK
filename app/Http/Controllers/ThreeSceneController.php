<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\ThreeScene;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Storage;

class ThreeSceneController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $user = $request->user();

        $items = ThreeScene::query()
            ->where('user_id', $user->id)
            ->orderByDesc('updated_at')
            ->limit(50)
            ->get(['id', 'name', 'updated_at', 'created_at']);

        return response()->json([
            'items' => $items,
        ]);
    }

    public function show(Request $request, int $sceneId): JsonResponse
    {
        $user = $request->user();
        $isAdmin = $user && (!method_exists($user, 'inRole') || !$user->inRole('client'));

        $query = ThreeScene::query()->with('quotes')->where('id', $sceneId);
        if (!$isAdmin) {
            $query->where('user_id', $user->id);
        }

        $scene = $query->firstOrFail();

        $latestQuote = $scene->quotes->sortByDesc('id')->first();
        $quoteStatus = $latestQuote ? (string) $latestQuote->status : null;
        $isLockedByQuote = in_array($quoteStatus, ['sent', 'sold'], true);
        $isNotOwner = (int) $scene->user_id !== (int) $user->id;
        $isReadOnly = $isLockedByQuote || $isNotOwner;

        return response()->json([
            'item' => [
                'id' => $scene->id,
                'name' => $scene->name,
                'data' => $scene->data,
                'read_only' => $isReadOnly,
                'quote_status' => $quoteStatus,
                'updated_at' => $scene->updated_at,
                'created_at' => $scene->created_at,
            ],
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $user = $request->user();

        // Límite de seguridad: máximo 50 escenas por usuario.
        if (ThreeScene::query()->where('user_id', $user->id)->count() >= 50) {
            return response()->json([
                'message' => 'Has alcanzado el límite máximo de 50 escenas guardadas. Elimina algunas antes de crear una nueva.',
            ], 422);
        }

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'data' => ['required', 'array'],
        ]);

        $scene = ThreeScene::query()->create([
            'user_id' => $user->id,
            'name' => $validated['name'],
            'data' => $validated['data'],
        ]);

        return response()->json([
            'item' => [
                'id' => $scene->id,
                'name' => $scene->name,
                'updated_at' => $scene->updated_at,
                'created_at' => $scene->created_at,
            ],
        ], 201);
    }

    public function update(Request $request, int $sceneId): JsonResponse
    {
        $user = $request->user();

        $scene = ThreeScene::query()
            ->with('quotes')
            ->where('user_id', $user->id)
            ->where('id', $sceneId)
            ->firstOrFail();

        $hasSentOrSoldQuote = $scene->quotes->contains(function ($quote) {
            return in_array($quote->status, ['sent', 'sold'], true);
        });

        if ($hasSentOrSoldQuote) {
            return response()->json([
                'message' => 'Esta escena ya fue enviada o vendida y solo puede visualizarse. Crea un nuevo escenario si deseas realizar otro diseño.',
            ], 422);
        }

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'data' => ['required', 'array'],
        ]);

        $scene->fill([
            'name' => $validated['name'],
            'data' => $validated['data'],
        ])->save();

        return response()->json([
            'item' => [
                'id' => $scene->id,
                'name' => $scene->name,
                'updated_at' => $scene->updated_at,
                'created_at' => $scene->created_at,
            ],
        ]);
    }

    public function destroy(Request $request, int $sceneId): JsonResponse|RedirectResponse
    {
        $user = $request->user();
        abort_unless($user, 403);

        // Solo el propietario puede eliminar su propio proyecto
        $scene = ThreeScene::query()
            ->with('quotes')
            ->where('user_id', $user->id)
            ->where('id', $sceneId)
            ->firstOrFail();

        // Bloquear eliminación si ya tiene una cotización enviada o vendida
        $hasSentOrSoldQuote = $scene->quotes->contains(function ($quote) {
            return in_array($quote->status, ['sent', 'sold'], true);
        });

        if ($hasSentOrSoldQuote) {
            $msg = 'No se puede eliminar este proyecto porque su cotización ya fue enviada al administrador o vendida.';
            if ($request->wantsJson()) {
                return response()->json(['message' => $msg], 422);
            }
            return redirect()->route('three.menu')->with('error', $msg);
        }

        // Limpiar archivos PDF en borrador si existen
        foreach ($scene->quotes as $quote) {
            if ($quote->pdf_path && Storage::disk('public')->exists($quote->pdf_path)) {
                Storage::disk('public')->delete($quote->pdf_path);
            }
        }

        $sceneName = $scene->name;
        $scene->delete();

        if ($request->wantsJson()) {
            return response()->json(['message' => 'Proyecto eliminado correctamente.']);
        }

        return redirect()->route('three.menu')->with('status', "El proyecto \"{$sceneName}\" fue eliminado correctamente.");
    }
}
