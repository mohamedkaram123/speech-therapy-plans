<?php

namespace Database\Seeders;

use App\Enums\StudentPeriod;
use App\Enums\UserRole;
use App\Models\BankGoal;
use App\Models\Student;
use App\Models\TherapyPlan;
use App\Models\User;
use App\Services\PlanWorkflowService;
use Illuminate\Database\Seeder;

/**
 * Demo data with entirely fictional names (never real people).
 *   php artisan db:seed --class=DemoSeeder
 * Run `php artisan speech:import-goals-bank` first so plans can use bank goals.
 */
class DemoSeeder extends Seeder
{
    public function run(PlanWorkflowService $plans): void
    {
        $this->call(UserSeeder::class);

        $supervisor = User::where('email', 'supervisor@example.com')->firstOrFail();

        $teacherNames = ['ندى العتيبي', 'ريم الفهد', 'جود السالم'];
        $teachers = collect($teacherNames)->map(fn (string $name, int $i) => User::updateOrCreate(
            ['email' => 'demo.teacher'.($i + 1).'@example.com'],
            ['name' => $name, 'password' => 'password', 'role' => UserRole::Teacher, 'is_active' => true, 'email_verified_at' => now()],
        ));

        $studentNames = [
            'زياد المنصور', 'لجين الحمدان', 'مازن الرشيد', 'سديم العمري', 'تميم الخالدي',
            'هلا البلوي', 'عاصم النعيمي', 'روان الزيد', 'يزن الحارثي',
        ];

        $students = collect($studentNames)->map(fn (string $name, int $i) => Student::firstOrCreate(
            ['full_name' => $name],
            [
                'teacher_id' => $teachers[$i % 3]->id,
                'period' => $i % 2 === 0 ? StudentPeriod::Morning : StudentPeriod::Evening,
                'birth_date_hijri' => sprintf('14%02d/%02d/%02d', 38 + ($i % 4), 1 + ($i % 12), 1 + ($i * 3 % 28)),
                'diagnosis' => ['تأخر لغوي', 'اضطراب نطق', 'صعوبات تواصل'][$i % 3],
            ],
        ));

        $bank = BankGoal::inRandomOrder()->limit(60)->get();
        if ($bank->isEmpty()) {
            $this->command?->warn('بنك الأهداف فارغ — تم تخطي إنشاء الخطط التجريبية.');

            return;
        }

        $planData = [
            'academic_year' => '1448-1449', 'semester' => 'first',
            'start_date_hijri' => '1448/02/05', 'end_date_hijri' => '1448/06/20',
            'sessions_count' => 24, 'sessions_per_week' => 2,
        ];

        // Statuses: approved, pending, returned, draft, (rest without plans)
        foreach ($students->take(4) as $i => $student) {
            if (TherapyPlan::where('student_id', $student->id)->where('academic_year', '1448-1449')->exists()) {
                continue;
            }

            $goals = $bank->slice($i * 3, 3)->map(fn (BankGoal $g) => ['bank_goal_id' => $g->id])->values()->all();
            $teacher = User::find($student->teacher_id);

            $plan = $plans->saveDraft(new TherapyPlan, [...$planData, 'student_id' => $student->id, 'teacher_id' => $teacher->id], $goals);

            if ($i === 3) {
                continue; // stays draft
            }
            $plans->submit($plan, $teacher);
            if ($i === 0) {
                $plans->approve($plan, $supervisor, 'خطة مناسبة.');
            } elseif ($i === 2) {
                $plans->returnPlan($plan, $supervisor, 'يرجى مراجعة صياغة الأهداف وإضافة ملاحظات.');
            }
        }
    }
}
