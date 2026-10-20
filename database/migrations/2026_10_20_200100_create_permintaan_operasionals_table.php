<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Permintaan relawan agar admin MEMULAI / MENUTUP operasi posko.
     * Relawan tidak bisa mengubah status operasional sendiri.
     */
    public function up(): void
    {
        Schema::create('permintaan_operasionals', function (Blueprint $table) {
            $table->id();

            $table->foreignId('lokasi_id')
                ->constrained('lokasis')
                ->cascadeOnDelete();

            // Relawan yang meminta
            $table->foreignId('user_id')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();

            $table->string('aksi', 10);                       // mulai | tutup
            $table->string('status', 15)->default('menunggu'); // menunggu | disetujui | ditolak
            $table->text('alasan')->nullable();               // alasan dari relawan
            $table->text('catatan_admin')->nullable();        // alasan penolakan dari admin

            $table->foreignId('diproses_oleh')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();
            $table->timestamp('diproses_at')->nullable();

            $table->timestamps();

            $table->index(['lokasi_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('permintaan_operasionals');
    }
};
