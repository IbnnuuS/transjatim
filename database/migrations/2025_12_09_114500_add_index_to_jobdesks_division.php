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
        Schema::table('jobdesks', function (Blueprint $table) {
            // Index 'division' for faster exact match filtering
            if (Schema::hasColumn('jobdesks', 'division')) {
                $table->index('division');
            }
            // Index 'divisi' legacy column just in case
            if (Schema::hasColumn('jobdesks', 'divisi')) {
                $table->index('divisi');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('jobdesks', function (Blueprint $table) {
            if (Schema::hasColumn('jobdesks', 'division')) {
                $table->dropIndex(['division']);
            }
            if (Schema::hasColumn('jobdesks', 'divisi')) {
                $table->dropIndex(['divisi']);
            }
        });
    }
};
