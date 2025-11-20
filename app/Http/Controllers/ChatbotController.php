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
        ]);

        $reply = $this->chatbot->chat($validated['message'], $validated['module'] ?? null);

        return response()->json([
            'reply' => $reply,
        ]);
    }
}
