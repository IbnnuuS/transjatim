<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('jobdesks', function (Blueprint $table) {
            if (!Schema::hasColumn('jobdesks', 'assignment_id')) {
                $table->unsignedBigInteger('assignment_id')->nullable()->after('user_id');
                $table->index('assignment_id', 'jobdesks_assignment_id_idx');
            }
        });
    }

    public function down(): void
    {
        Schema::table('jobdesks', function (Blueprint $table) {
            if (Schema::hasColumn('jobdesks', 'assignment_id')) {
                $table->dropIndex('jobdesks_assignment_id_idx');
                $table->dropColumn('assignment_id');
            }
        });
    }
};
