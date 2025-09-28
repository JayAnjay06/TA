<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\AiAnalisis;
use App\Models\Laporan;
use Illuminate\Support\Facades\Http;
use Carbon\Carbon;

class AiAnalisisController extends Controller
{
    // Tampilkan semua analisis
    public function index()
    {
        $data = AiAnalisis::with('laporan')->orderBy('created_at', 'desc')->get();
        return response()->json($data);
    }

    // Analisis laporan berbasis teks
    public function store(Request $request)
    {
        $request->validate([
            'laporan_id' => 'required|exists:laporans,laporan_id',
        ]);

        $laporan = Laporan::findOrFail($request->laporan_id);

        // input text dari laporan
        $inputText = "Analisis laporan mangrove berikut:\n"
            ."Jenis Laporan: {$laporan->jenis_laporan}\n"
            ."Isi: {$laporan->isi_laporan}\n"
            ."Lokasi ID: {$laporan->lokasi_id}";

        // default hasil jika parsing gagal
        $hasil_jenis = "Tidak diketahui";
        $hasil_kondisi = "Tidak diketahui";
        $confidence = 0.0;
        $rekomendasi = "Belum ada rekomendasi.";

        // Kirim ke OpenRouter AI
        $response = Http::withHeaders([
            'Authorization' => 'Bearer ' . env('OPENROUTER_API_KEY'),
            'Content-Type'  => 'application/json',
        ])->post('https://openrouter.ai/api/v1/chat/completions', [
            'model' => 'x-ai/grok-4-fast:freeopenai/gpt-3.5-turbo',
            'messages' => [
                [
                    'role' => 'system',
                    'content' => 'Kamu adalah analis ekosistem mangrove. Jawab SELALU dalam format JSON rapi: 
                    { "hasil_jenis": "...", "hasil_kondisi": "...", "confidence": 0.xx, "rekomendasi": "..." }'
                ],
                ['role' => 'user', 'content' => $inputText],
            ],
        ]);

        $jsonResult = $response->json('choices.0.message.content');

        // parsing JSON jawaban
        $parsed = json_decode($jsonResult, true);
        if (is_array($parsed)) {
            $hasil_jenis = $parsed['hasil_jenis'] ?? $hasil_jenis;
            $hasil_kondisi = $parsed['hasil_kondisi'] ?? $hasil_kondisi;
            $confidence = $parsed['confidence'] ?? $confidence;
            $rekomendasi = $parsed['rekomendasi'] ?? $rekomendasi;
        }

        // Simpan ke DB
        $data = AiAnalisis::create([
            'laporan_id'       => $laporan->laporan_id,
            'hasil_jenis'      => $hasil_jenis,
            'hasil_kondisi'    => $hasil_kondisi,
            'confidence'       => $confidence,
            'rekomendasi'      => $rekomendasi,
            'tanggal_analisis' => Carbon::now(),
        ]);

        return response()->json($data, 201);
    }
}
