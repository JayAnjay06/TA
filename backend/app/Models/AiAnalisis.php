<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AiAnalisis extends Model
{
    protected $table = 'ai_analisis';
    protected $primaryKey = 'analisis_id';

    protected $fillable = [
        'laporan_id',
        'hasil_jenis',
        'hasil_kondisi',
        'confidence',
        'rekomendasi',
        'tanggal_analisis',
    ];

    public function laporan()
    {
        return $this->belongsTo(Laporan::class, 'laporan_id');
    }
}
