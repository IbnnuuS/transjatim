<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('daily_reports', function (Blueprint $table) {
            $table->id();

            // Relasi user
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade');

            // Identitas pelapor
            $table->string('nama');
            $table->string('divisi');

            // Waktu laporan
            $table->dateTime('tanggal_laporan')->useCurrent();

            // Detail pekerjaan
            $table->text('pekerjaan')->comment('Daftar pekerjaan dipisahkan koma');
            $table->string('title');                    // Judul pekerjaan (untuk tabel admin)
            $table->string('pic')->nullable();          // PIC
            $table->unsignedTinyInteger('progress')->default(0); // 0-100

            // Bukti & lokasi
            $table->string('photo_url')->nullable();    // path foto dari kamera
            $table->decimal('latitude', 10, 7)->nullable();
            $table->decimal('longitude', 10, 7)->nullable();

            // Lain-lain
            $table->text('catatan')->nullable();
            $table->string('status', 20)->default('pending'); // pending|in_progress|done|blocked

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('daily_reports');
    }
};
