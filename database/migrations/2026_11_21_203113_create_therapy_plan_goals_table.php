<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('therapy_plan_goals', function (Blueprint $table) {
            $table->id();
            $table->foreignId('therapy_plan_id')->constrained('therapy_plans')->cascadeOnDelete();
            $table->foreignId('bank_goal_id')->nullable()->constrained('bank_goals')->nullOnDelete();
            $table->string('domain_name');
            $table->string('level')->nullable();
            $table->string('subcategory')->nullable();
            $table->string('printed_code', 50)->nullable();
            $table->text('goal_text');
            $table->text('training_elements')->nullable();
            $table->text('generalization')->nullable();
            $table->text('teacher_note')->nullable();
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();

            $table->index(['therapy_plan_id', 'sort_order']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('therapy_plan_goals');
    }
};
