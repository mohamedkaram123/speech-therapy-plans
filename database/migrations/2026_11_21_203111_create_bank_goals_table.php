<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('bank_goals', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->foreignId('goal_domain_id')->constrained('goal_domains');
            $table->string('level')->nullable()->index();
            $table->string('subcategory')->nullable()->index();
            $table->string('printed_code', 50)->nullable()->index();
            $table->text('goal_text');
            $table->text('training_elements')->nullable();
            $table->text('generalization')->nullable();
            $table->string('fingerprint', 64)->unique(); // sha256 of normalized content
            $table->boolean('needs_review')->default(false)->index();
            $table->text('review_reason')->nullable();
            $table->timestamps();
        });

        if (DB::getDriverName() === 'mysql') {
            Schema::table('bank_goals', function (Blueprint $table) {
                $table->fullText('goal_text');
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('bank_goals');
    }
};
