<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\ThreeScene;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class ThreeSceneController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $user = $request->user();

        $items = ThreeScene::query()
            ->where('user_id', $user->id)
            ->orderByDesc('updated_at')
            ->get(['id', 'name', 'updated_at', 'created_at']);

        return response()->json([
            'items' => $items,
        ]);
    }

    public function show(Request $request, int $sceneId): JsonResponse
    {
        $user = $request->user();

        $scene = ThreeScene::query()
            ->where('user_id', $user->id)
            ->where('id', $sceneId)
            ->firstOrFail();

        return response()->json([
            'item' => [
                'id' => $scene->id,
                'name' => $scene->name,
                'data' => $scene->data,
                'updated_at' => $scene->updated_at,
                'created_at' => $scene->created_at,
            ],
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $user = $request->user();

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
            ->where('user_id', $user->id)
            ->where('id', $sceneId)
            ->firstOrFail();

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
}
