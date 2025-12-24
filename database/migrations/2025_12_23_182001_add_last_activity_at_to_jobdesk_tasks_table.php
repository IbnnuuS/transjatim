<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('jobdesk_tasks', function (Blueprint $table) {
            $table->timestamp('last_activity_at')->nullable()->index()->after('updated_at');
        });
    }

    public function down(): void
    {
        Schema::table('jobdesk_tasks', function (Blueprint $table) {
            $table->dropColumn('last_activity_at');
        });
    }
};
