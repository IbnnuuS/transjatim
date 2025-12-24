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
        Schema::table('jobdesk_tasks', function (Blueprint $table) {
            $table->string('proof_link')->nullable()->after('detail');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('jobdesk_tasks', function (Blueprint $table) {
            $table->dropColumn('proof_link');
        });
    }
};
