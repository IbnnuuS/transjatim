<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('assignments', function (Blueprint $table) {
            $table->id();
            $table->string('title'); // judul tugas
            $table->date('deadline'); // tanggal deadline
            $table->foreignId('employee_id')->constrained('users')->cascadeOnDelete(); // karyawan (user)
            $table->string('description', 255)->nullable(); // deskripsi singkat
            $table->string('status', 20)->default('in_progress'); // pending|in_progress|done
            $table->unsignedTinyInteger('progress')->default(0); // 0–100%
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('assignments');
    }
};
