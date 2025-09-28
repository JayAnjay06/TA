<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\AiChat;
use Illuminate\Support\Facades\Http;

class AiChatController extends Controller
{
    public function index()
    {
        $chats = AiChat::select('chat_id','pertanyaan','jawaban','tanggal_chat')
            ->orderBy('tanggal_chat', 'desc')
            ->get();

        return response()->json($chats);
    }

    public function store(Request $request)
    {
        $request->validate([
            'pertanyaan' => 'required|string',
        ]);

        $pertanyaan = $request->pertanyaan;
        $jawaban = "";

        if (!preg_match('/mangrove/i', $pertanyaan)) {
            $jawaban = "Maaf, saya kurang mengerti pertanyaan Anda. Saya hanya bisa membantu seputar mangrove.";
        } else {
            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . env('OPENROUTER_API_KEY'),
                'Content-Type'  => 'application/json',
            ])->post('https://openrouter.ai/api/v1/chat/completions', [
                'model' => 'x-ai/grok-4-fast:free',
                'messages' => [
                    [
                        'role' => 'system',
                        'content' => 'Kamu adalah asisten pakar mangrove. Jawablah hanya seputar tanaman mangrove dengan bahasa Indonesia sesuai EYD. Jangan gunakan emotikon, simbol, atau karakter aneh.'
                    ],
                    ['role' => 'user', 'content' => $pertanyaan],
                ],
            ]);

            $jawaban = $response->json('choices.0.message.content') ?? "Maaf, AI tidak merespon.";
        }

        $jawaban = preg_replace('/[^A-Za-z0-9À-ÿ\s\.,;:!\?]/u', '', $jawaban); 
        $jawaban = str_replace(["\n", "\r"], ' ', $jawaban); 
        $jawaban = preg_replace('/\s+/', ' ', $jawaban); 

        $chat = AiChat::create([
            'user_id' => $request->user_id, 
            'pertanyaan' => $pertanyaan,
            'jawaban' => trim($jawaban),
        ]);

        return response()->json($chat, 201);
    }
}
