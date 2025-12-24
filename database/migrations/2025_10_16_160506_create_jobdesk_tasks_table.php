<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::create('jobdesk_tasks', function (Blueprint $table) {
            $table->id();
            $table->foreignId('jobdesk_id')
                ->constrained('jobdesks')
                ->cascadeOnUpdate()
                ->cascadeOnDelete();

            // Data inti
            $table->string('judul');
            $table->string('pic');
            $table->date('schedule_date');
            $table->time('start_time')->nullable();
            $table->time('end_time')->nullable();

            // Status & progress
            $table->enum('status', [
                'done','in_progress','pending','cancelled','verification','delayed','rework','to_do'
            ])->default('to_do');
            $table->unsignedTinyInteger('progress')->default(1); // 1..100

            // Deskripsi
            $table->text('result')->nullable();
            $table->text('shortcoming')->nullable();
            $table->longText('detail')->nullable();

            // Lokasi
            $table->decimal('lat', 10, 7)->nullable();
            $table->decimal('lng', 10, 7)->nullable();
            $table->string('address')->nullable();

            $table->timestamps();

            $table->index(['jobdesk_id', 'schedule_date']);
            $table->index(['status', 'progress']);
            $table->index(['lat', 'lng']);
        });
    }

    public function down(): void {
        Schema::dropIfExists('jobdesk_tasks');
    }
};
