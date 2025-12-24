<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('jobdesks', function (Blueprint $table) {
            // Tambah division jika belum ada (opsional, hanya jika kamu butuh kolom ini)
            if (!Schema::hasColumn('jobdesks', 'division')) {
                $table->string('division', 100)->nullable()->after('user_id');
            }

            // Tambah submitted_at jika belum ada
            if (!Schema::hasColumn('jobdesks', 'submitted_at')) {
                // taruh setelah division jika ada; jika tidak, setelah user_id
                if (Schema::hasColumn('jobdesks', 'division')) {
                    $table->dateTime('submitted_at')->nullable()->after('division');
                } else {
                    $table->dateTime('submitted_at')->nullable()->after('user_id');
                }
            }
        });

        // Backfill submitted_at = created_at agar tidak null
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
            // Jika ingin ikut hapus division (hati-hati):
            // if (Schema::hasColumn('jobdesks', 'division')) {
            //     $table->dropColumn('division');
            // }
        });
    }
};
