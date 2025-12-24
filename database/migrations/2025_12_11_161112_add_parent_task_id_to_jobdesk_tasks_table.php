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
            $table->unsignedBigInteger('parent_task_id')->nullable()->after('jobdesk_id')->index();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('jobdesk_tasks', function (Blueprint $table) {
            $table->dropColumn('parent_task_id');
        });
    }
};
