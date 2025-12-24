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
        Schema::table('assignments', function (Blueprint $table) {
            $table->string('proof_link', 2056)->nullable()->after('progress');
            $table->text('result')->nullable()->after('proof_link');
            $table->text('shortcoming')->nullable()->after('result');
            $table->text('detail')->nullable()->after('shortcoming');
        });
    }

    public function down(): void
    {
        Schema::table('assignments', function (Blueprint $table) {
            $table->dropColumn(['proof_link', 'result', 'shortcoming', 'detail']);
        });
    }
};
