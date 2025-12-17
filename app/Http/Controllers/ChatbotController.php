<?php

namespace App\Http\Controllers;

use App\Services\ChatbotService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class ChatbotController extends Controller
{
    public function __construct(private ChatbotService $chatbot)
    {
    }

    public function handle(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'message' => 'required|string|max:2000',
            'module'  => 'nullable|string|max:100',
            'path'    => 'nullable|string|max:255',
        ]);

        $module = $validated['module'] ?? null;
        if (!$module && !empty($validated['path'])) {
            $module = $this->inferModuleFromPath($validated['path']);
        }

        $reply = $this->chatbot->chat($validated['message'], $module);

        return response()->json([
            'reply' => $reply,
        ]);
    }

    private function inferModuleFromPath(string $path): ?string
    {
        $p = strtolower($path);

        if (str_contains($p, '/admin/users')) return 'usuarios';
        if (str_contains($p, '/admin/roles')) return 'roles';
        if (str_contains($p, '/admin/producto') || str_contains($p, '/admin/productos') || str_contains($p, '/admin/crud/list/producto-resources')) return 'productos';
        if (str_contains($p, '/admin/inventario') || str_contains($p, '/admin/inventarios')) return 'inventarios';
        if (str_contains($p, '/admin/proyecto') || str_contains($p, '/admin/proyectos')) return 'proyectos';
        if (str_contains($p, '/admin/escena') || str_contains($p, '/admin/escenas')) return 'escenas';

        return null;
    }
}
