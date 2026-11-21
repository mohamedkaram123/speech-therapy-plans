<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Fresh installs already have the new shape from create_students_table.
        if (! Schema::hasTable('students')) {
            return;
        }

        $needsAcademic = ! Schema::hasColumn('students', 'academic_year');
        $driver = Schema::getConnection()->getDriverName();

        if ($driver === 'mysql') {
            // Existing production DB may still have NOT NULL teacher_id/period.
            Schema::table('students', function (Blueprint $table) {
                $table->dropForeign(['teacher_id']);
            });
            DB::statement('ALTER TABLE students MODIFY teacher_id BIGINT UNSIGNED NULL');
            DB::statement('ALTER TABLE students MODIFY period VARCHAR(20) NULL');
            Schema::table('students', function (Blueprint $table) use ($needsAcademic) {
                if ($needsAcademic) {
                    $table->string('academic_year', 20)->nullable()->after('period');
                    $table->string('semester', 20)->nullable()->after('academic_year');
                }
                $table->foreign('teacher_id')->references('id')->on('users')->nullOnDelete();
            });

            return;
        }

        if ($needsAcademic) {
            Schema::table('students', function (Blueprint $table) {
                $table->string('academic_year', 20)->nullable();
                $table->string('semester', 20)->nullable();
            });
        }
    }

    public function down(): void
    {
        if (! Schema::hasTable('students')) {
            return;
        }

        Schema::table('students', function (Blueprint $table) {
            if (Schema::hasColumn('students', 'academic_year')) {
                $table->dropColumn('academic_year');
            }
            if (Schema::hasColumn('students', 'semester')) {
                $table->dropColumn('semester');
            }
        });
    }
};
