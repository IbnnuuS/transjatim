<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('schedules', function (Blueprint $table) {
            $table->id();

            // Relasi ke users (penjadwalan untuk masing-masing karyawan)
            $table->foreignId('user_id')
                ->constrained()
                ->cascadeOnUpdate()
                ->cascadeOnDelete();

            // Kolom-kolom yang muncul di tabel Blade kamu
            $table->date('tanggal');                 // Tanggal jadwal
            $table->string('periode', 50);           // Periode (mis. Pagi/Siang/Malam/Full Day)
            $table->string('divisi', 100)->nullable(); // Divisi (opsional)
            $table->string('judul', 200);            // Judul/Deskripsi singkat jadwal
            $table->text('catatan')->nullable();     // Catatan (opsional)

            // (Opsional) jam mulai & selesai kalau nanti dibutuhkan
            $table->time('jam_mulai')->nullable();
            $table->time('jam_selesai')->nullable();

            $table->timestamps();

            // Index yang membantu pencarian/filter
            $table->index(['user_id', 'tanggal']);
            $table->index('divisi');
            $table->index('periode');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('schedules');
    }
};
