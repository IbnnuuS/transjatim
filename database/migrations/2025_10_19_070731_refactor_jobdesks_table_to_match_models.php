<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('jobdesks', function (Blueprint $table) {
            // 1) Rename 'divisi' -> 'division' jika kolom lama masih ada
            if (Schema::hasColumn('jobdesks', 'divisi') && !Schema::hasColumn('jobdesks', 'division')) {
                $table->renameColumn('divisi', 'division');
            }

            // 2) Pastikan 'division' ada (kalau sebelumnya tak ada sama sekali)
            if (!Schema::hasColumn('jobdesks', 'division')) {
                $table->string('division', 100)->nullable()->after('user_id');
            }

            // 3) Tambahkan 'submitted_at' sesuai model
            if (!Schema::hasColumn('jobdesks', 'submitted_at')) {
                // pakai useCurrent agar insert aman tanpa set manual
                $table->dateTime('submitted_at')->nullable()->useCurrent()->after('division');
            }

            // 4) Kolom lama yang tidak dipakai lagi bisa DIHAPUS agar tidak bikin rancu:
            //    'tanggal', 'tasks'(json), 'note' ——> hanya kalau kamu sudah migrasi ke jobdesk_tasks.
            if (Schema::hasColumn('jobdesks', 'tanggal')) {
                $table->dropColumn('tanggal');
            }
            if (Schema::hasColumn('jobdesks', 'tasks')) {
                $table->dropColumn('tasks');
            }
            if (Schema::hasColumn('jobdesks', 'note')) {
                $table->dropColumn('note');
            }
        });

        // 5) Backfill submitted_at dari created_at kalau masih null
        DB::table('jobdesks')->whereNull('submitted_at')->update([
            'submitted_at' => DB::raw('created_at')
        ]);
    }

    public function down(): void
    {
        Schema::table('jobdesks', function (Blueprint $table) {
            if (Schema::hasColumn('jobdesks', 'submitted_at')) {
                $table->dropColumn('submitted_at');
            }
            // Kembalikan 'division' ke 'divisi' (opsional — hati-hati)
            if (Schema::hasColumn('jobdesks', 'division') && !Schema::hasColumn('jobdesks', 'divisi')) {
                $table->renameColumn('division', 'divisi');
            }
            // Kolom lama (tanggal/tasks/note) tidak di-restore demi kesederhanaan.
        });
    }
};
