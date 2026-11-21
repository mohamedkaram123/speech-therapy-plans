<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('evaluations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('therapy_plan_id')->unique()->constrained('therapy_plans');
            $table->enum('status', ['draft', 'pending_review', 'returned', 'approved'])->default('draft')->index();
            $table->string('evaluation_completed_at_hijri', 20)->nullable();

            // supervisor report fields
            $table->text('performance_summary')->nullable();
            $table->text('strengths')->nullable();
            $table->text('skills_needing_training')->nullable();
            $table->text('observed_progress')->nullable();
            $table->text('final_notes')->nullable();
            $table->text('recommendations')->nullable();

            $table->timestamp('submitted_at')->nullable();
            $table->timestamp('approved_at')->nullable();
            $table->foreignId('approved_by')->nullable()->constrained('users')->nullOnDelete();
            $table->json('locked_snapshot')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('evaluations');
    }
};
