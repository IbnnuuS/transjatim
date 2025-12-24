<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('jobdesk_tasks', function (Blueprint $table) {
            $table->unsignedBigInteger('assignment_id')
                ->nullable()
                ->index()
                ->after('jobdesk_id');
        });
    }

    public function down(): void
    {
        Schema::table('jobdesk_tasks', function (Blueprint $table) {
            $table->dropColumn('assignment_id');
        });
    }
};
