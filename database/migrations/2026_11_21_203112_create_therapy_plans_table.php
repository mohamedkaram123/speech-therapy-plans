<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('therapy_plans', function (Blueprint $table) {
            $table->id();
            // live references
            $table->foreignId('student_id')->constrained('students');
            $table->foreignId('teacher_id')->constrained('users');

            // snapshots (frozen at approval, also filled on create)
            $table->string('student_name');
            $table->string('teacher_name');
            $table->string('birth_date_hijri', 20)->nullable();
            $table->enum('period', ['morning', 'evening']);
            $table->text('diagnosis')->nullable();
            $table->string('academic_year', 20);
            $table->enum('semester', ['first', 'second']);
            $table->string('start_date_hijri', 20);
            $table->string('end_date_hijri', 20);
            $table->unsignedInteger('sessions_count');
            $table->unsignedInteger('sessions_per_week');

            $table->enum('status', ['draft', 'pending_review', 'returned', 'approved'])->default('draft')->index();
            $table->timestamp('submitted_at')->nullable();
            $table->timestamp('approved_at')->nullable();
            $table->foreignId('approved_by')->nullable()->constrained('users')->nullOnDelete();
            $table->json('locked_snapshot')->nullable();
            $table->timestamps();

            $table->index(['student_id', 'academic_year', 'semester']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('therapy_plans');
    }
};
