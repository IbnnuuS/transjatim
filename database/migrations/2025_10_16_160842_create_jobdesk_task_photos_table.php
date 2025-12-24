<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::create('jobdesk_task_photos', function (Blueprint $table) {
            $table->id();
            $table->foreignId('task_id')
                  ->constrained('jobdesk_tasks')
                  ->cascadeOnDelete();
            $table->string('path'); // relative to storage/app/public, ex: jobdesk/2025/10/16/xxx.jpg
            $table->timestamps();

            $table->index('task_id');
        });
    }

    public function down(): void {
        Schema::dropIfExists('jobdesk_task_photos');
    }
};
