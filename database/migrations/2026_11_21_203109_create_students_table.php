<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('students', function (Blueprint $table) {
            $table->id();
            $table->string('full_name')->index();
            $table->date('birth_date')->nullable(); // Gregorian
            $table->string('birth_date_hijri', 20)->nullable();
            $table->foreignId('teacher_id')->constrained('users');
            $table->enum('period', ['morning', 'evening']);
            $table->text('diagnosis')->nullable();
            $table->softDeletes();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('students');
    }
};
