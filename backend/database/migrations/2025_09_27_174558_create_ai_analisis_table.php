<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('ai_analisis', function (Blueprint $table) {
            $table->id('analisis_id');
            $table->unsignedBigInteger('laporan_id');
            $table->string('hasil_jenis')->nullable();
            $table->string('hasil_kondisi')->nullable();
            $table->float('confidence')->nullable();
            $table->text('rekomendasi')->nullable();
            $table->dateTime('tanggal_analisis');
            $table->timestamps();

            $table->foreign('laporan_id')
                  ->references('laporan_id')
                  ->on('laporans')
                  ->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('ai_analises');
    }
};
