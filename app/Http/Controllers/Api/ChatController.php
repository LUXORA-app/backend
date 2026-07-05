<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\ChatMessage;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;

class ChatController extends Controller
{
    public function sendMessage(Request $request)
    {
        $request->validate([
            'message' => 'required|string',
        ]);

        // Save user message
        $userMessage = $request->user()->chatMessages()->create([
            'message' => $request->message,
            'is_bot' => false,
        ]);

        // Get chat history for context
        $chatHistory = $request->user()->chatMessages()->orderBy('created_at', 'asc')->get();
        $messagesForML = [];
        foreach ($chatHistory as $msg) {
            $messagesForML[] = [
                'role' => $msg->is_bot ? 'assistant' : 'user',
                'content' => $msg->message
            ];
        }

        // Call ML API
        try {
            $mlResponse = Http::timeout(30)->post('http://localhost:8001/chat', [
                'messages' => $messagesForML
            ]);

            if ($mlResponse->successful()) {
                $mlData = $mlResponse->json();
                $botResponseText = $mlData['bot_message']['message'];
            } else {
                $botResponseText = "Sorry, I couldn't connect to the AI service. Please try again later.";
            }
        } catch (\Exception $e) {
            $botResponseText = "Sorry, an error occurred: " . $e->getMessage();
        }

        // Save bot response
        $botMessage = $request->user()->chatMessages()->create([
            'message' => $botResponseText,
            'is_bot' => true,
        ]);

        return response()->json([
            'user_message' => $userMessage,
            'bot_message' => $botMessage,
        ]);
    }

    public function history(Request $request)
    {
        return response()->json($request->user()->chatMessages()->orderBy('created_at', 'asc')->get());
    }
}
