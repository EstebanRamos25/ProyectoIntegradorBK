<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\ThreeScene;
use App\Services\ThreeRecommendationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class ThreeRecommendationController extends Controller
{
    public function index(Request $request, ThreeRecommendationService $service): JsonResponse
    {
        $userId = (int) (Auth::id() ?? 0);
        if ($userId <= 0) {
            return response()->json(['message' => 'Unauthorized'], 401);
        }

        $validated = $request->validate([
            'scene_id' => ['required', 'integer', 'min:1'],
            'limit' => ['nullable', 'integer', 'min:1', 'max:12'],
        ]);

        $sceneId = (int) $validated['scene_id'];
        $limit = (int) ($validated['limit'] ?? 6);

        $scene = ThreeScene::query()
            ->whereKey($sceneId)
            ->where('user_id', $userId)
            ->first();

        if (!$scene) {
            return response()->json(['message' => 'Escena no encontrada.'], 404);
        }

        $result = $service->recommendForUserScene($userId, $sceneId, $limit);

        return response()->json($result);
    }
}
