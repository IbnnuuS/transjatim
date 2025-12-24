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
        Schema::table('attendances', function (Blueprint $table) {
            if (!Schema::hasColumn('attendances', 'overtime_minutes')) {
                $table->integer('overtime_minutes')->default(0)->after('out_time');
            }
            if (!Schema::hasColumn('attendances', 'overtime_reason')) {
                $table->string('overtime_reason')->nullable()->after('overtime_minutes');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('attendances', function (Blueprint $table) {
            if (Schema::hasColumn('attendances', 'overtime_minutes')) {
                $table->dropColumn('overtime_minutes');
            }
            if (Schema::hasColumn('attendances', 'overtime_reason')) {
                $table->dropColumn('overtime_reason');
            }
        });
    }
};
