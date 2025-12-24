<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('attendances', function (Blueprint $table) {
            $table->id();
            $table->foreignId('employee_id')->constrained('users')->cascadeOnDelete();
            $table->date('date');
            $table->string('shift', 20)->nullable();      // Pagi/Siang/Malam
            $table->string('status', 20)->default('present'); // present|izin|leave|absent|late
            $table->string('division', 100)->nullable();
            $table->time('in_time')->nullable();
            $table->time('out_time')->nullable();
            $table->string('note', 255)->nullable();
            $table->timestamps();

            $table->index(['date', 'shift', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('attendances');
    }
};
