<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Tambah kolom tanggal ke tabel jobdesks.
     */
    public function up(): void
    {
        Schema::table('jobdesks', function (Blueprint $table) {
            // Cek dulu, jangan double tambah kolom
            if (!Schema::hasColumn('jobdesks', 'tanggal')) {
                // Sesuaikan tipe data dengan kebutuhan:
                // Kalau mau dateTime:
                $table->dateTime('tanggal')
                    ->nullable()
                    ->after('divisi');

                // Kalau lebih cocok date saja, bisa pakai:
                // $table->date('tanggal')->nullable()->after('divisi');
            }
        });
    }

    /**
     * Rollback: hapus kolom tanggal dari jobdesks.
     */
    public function down(): void
    {
        Schema::table('jobdesks', function (Blueprint $table) {
            if (Schema::hasColumn('jobdesks', 'tanggal')) {
                $table->dropColumn('tanggal');
            }
        });
    }
};
