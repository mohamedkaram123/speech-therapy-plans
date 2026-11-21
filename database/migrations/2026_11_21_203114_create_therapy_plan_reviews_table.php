<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('therapy_plan_reviews', function (Blueprint $table) {
            $table->id();
            $table->foreignId('therapy_plan_id')->constrained('therapy_plans')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained('users');
            $table->enum('action', ['submitted', 'returned', 'approved']);
            $table->text('note')->nullable();
            $table->timestamp('created_at')->useCurrent();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('therapy_plan_reviews');
    }
};
