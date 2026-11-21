<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('evaluation_goal_results', function (Blueprint $table) {
            $table->id();
            $table->foreignId('evaluation_id')->constrained('evaluations')->cascadeOnDelete();
            $table->foreignId('therapy_plan_goal_id')->constrained('therapy_plan_goals')->cascadeOnDelete();
            $table->enum('result', ['mastered', 'in_training', 'not_mastered'])->nullable(); // NULL = not rated
            $table->text('note')->nullable();
            $table->timestamps();

            $table->unique(['evaluation_id', 'therapy_plan_goal_id'], 'eval_goal_results_eval_goal_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('evaluation_goal_results');
    }
};
