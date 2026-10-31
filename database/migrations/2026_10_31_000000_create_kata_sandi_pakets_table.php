<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Kata sandi posko yang pernah berhasil membuka Paket Data Posko terkunci
 * (disimpan terenkripsi dengan APP_KEY). Dicoba otomatis untuk paket
 * berikutnya supaya admin cukup mengetiknya sekali per posko.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('kata_sandi_pakets', function (Blueprint $table) {
            $table->id();
            $table->text('kata_sandi'); // terenkripsi
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('kata_sandi_pakets');
    }
};
