<?php

namespace Tests\Feature;

use App\Enums\EvaluationStatus;
use App\Enums\PlanStatus;
use App\Enums\StudentPeriod;
use App\Models\BankGoal;
use App\Models\GoalDomain;
use App\Models\Student;
use App\Models\TherapyPlan;
use App\Models\User;
use App\Services\PlanWorkflowService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class UiSmokeTest extends TestCase
{
    use RefreshDatabase;

    private function sup(): User
    {
        return User::factory()->supervisor()->create(['name' => 'مشرف تجريبي']);
    }

    private function teacher(string $name = 'معلمة تجريبية'): User
    {
        return User::factory()->teacher()->create(['name' => $name]);
    }

    private function student(User $teacher, string $name = 'طالب تجريبي'): Student
    {
        return Student::create(['full_name' => $name, 'teacher_id' => $teacher->id, 'period' => StudentPeriod::Morning, 'birth_date_hijri' => '1440/05/12']);
    }

    private function bankGoal(): BankGoal
    {
        $domain = GoalDomain::factory()->create();

        return BankGoal::factory()->create(['goal_domain_id' => $domain->id]);
    }

    private function planFields(array $extra = []): array
    {
        return $extra + [
            'academic_year' => '1448-1449', 'semester' => 'first', 'start_date_hijri' => '1448/02/05',
            'end_date_hijri' => '1448/06/20', 'sessions_count' => 20, 'sessions_per_week' => 2,
        ];
    }

    public function test_guest_is_redirected_and_login_is_arabic(): void
    {
        $this->get('/dashboard')->assertRedirect('/login');
        $this->get('/login')->assertOk()->assertSee('dir="rtl"', false)->assertSee('تسجيل الدخول')->assertDontSee('register');
    }

    public function test_dashboards_per_role(): void
    {
        $sup = $this->sup();
        $teacher = $this->teacher();
        $student = $this->student($teacher);
        $plan = app(PlanWorkflowService::class)->saveDraft(new TherapyPlan, $this->planFields(['student_id' => $student->id, 'teacher_id' => $teacher->id]), [
            ['goal_text' => 'هدف', 'domain_name' => 'مجال'],
        ]);
        app(PlanWorkflowService::class)->submit($plan, $teacher);

        $this->actingAs($sup)->get('/dashboard')->assertOk()->assertSee('لوحة المشرف')->assertSee('طالب تجريبي');
        $this->actingAs($teacher)->get('/dashboard')->assertOk()->assertSee('مرحباً');
    }

    public function test_supervisor_manages_teachers_and_students(): void
    {
        $sup = $this->sup();

        $this->actingAs($sup)->post('/teachers', [
            'name' => 'معلم جديد', 'email' => 'new@example.com', 'password' => 'password1', 'password_confirmation' => 'password1',
        ])->assertRedirect('/teachers');
        $teacher = User::where('email', 'new@example.com')->firstOrFail();
        $this->assertTrue($teacher->isTeacher());

        $this->actingAs($sup)->get('/teachers')->assertOk()->assertSee('معلم جديد');
        $this->actingAs($sup)->patch("/teachers/{$teacher->id}/toggle")->assertRedirect();
        $this->assertFalse($teacher->fresh()->is_active);
        $this->actingAs($sup)->patch("/teachers/{$teacher->id}/toggle");
        $this->actingAs($sup)->get("/teachers/{$teacher->id}/edit")->assertOk();

        $this->actingAs($sup)->post('/students', [
            'full_name' => 'طالب جديد', 'teacher_id' => $teacher->id, 'period' => 'evening', 'birth_date' => '2016-03-01',
        ])->assertRedirect('/students');
        $student = Student::where('full_name', 'طالب جديد')->firstOrFail();
        $this->assertNotEmpty($student->birth_date_hijri);

        $this->actingAs($sup)->get('/students')->assertOk()->assertSee('طالب جديد');
        $this->actingAs($sup)->get("/students/{$student->id}/edit")->assertOk()->assertSee('هجري');
    }

    public function test_teacher_cannot_access_supervisor_areas_or_other_students(): void
    {
        $teacher = $this->teacher();
        $other = $this->teacher('معلمة أخرى');
        $mine = $this->student($teacher, 'طالبي');
        $theirs = $this->student($other, 'طالب آخر');

        $this->actingAs($teacher)->get('/teachers')->assertForbidden();
        $this->actingAs($teacher)->get('/tracking')->assertForbidden();
        $this->actingAs($teacher)->get('/students/create')->assertForbidden();
        $this->actingAs($teacher)->get('/students')->assertOk()->assertSee('طالبي')->assertDontSee('طالب آخر');
        $this->actingAs($teacher)->get("/students/{$theirs->id}")->assertForbidden();
        $this->actingAs($teacher)->get("/students/{$theirs->id}/plans/create")->assertForbidden();
        $this->actingAs($teacher)->get("/students/{$mine->id}/plans/create")->assertOk()->assertSee('هجري');
    }

    public function test_goals_bank_browse_and_json_search(): void
    {
        $teacher = $this->teacher();
        $domain = GoalDomain::factory()->create(['name' => 'مجال اختبار']);
        BankGoal::factory()->create(['goal_domain_id' => $domain->id, 'goal_text' => 'يسمي الألوان الأساسية', 'level' => 'مستوى 1', 'subcategory' => 'تصنيف أ']);
        BankGoal::factory()->create(['goal_domain_id' => $domain->id, 'goal_text' => 'يكرر الأصوات', 'level' => null]);

        $this->actingAs($teacher)->get('/goals-bank?q=الألوان')->assertOk()->assertSee('يسمي الألوان الأساسية')->assertDontSee('يكرر الأصوات');

        $this->actingAs($teacher)->getJson("/goals-bank/search.json?domain_id={$domain->id}&level=".urlencode('مستوى 1'))
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.goal_text', 'يسمي الألوان الأساسية')
            ->assertJsonPath('facets.levels.0', 'مستوى 1');
    }

    public function test_full_plan_and_evaluation_flow(): void
    {
        $sup = $this->sup();
        $teacher = $this->teacher();
        $student = $this->student($teacher);
        $goal = $this->bankGoal();

        // create + editor page
        $this->actingAs($teacher)->post('/plans', $this->planFields(['student_id' => $student->id]))->assertRedirect();
        $plan = TherapyPlan::firstOrFail();
        $this->assertSame(PlanStatus::Draft, $plan->status);
        $this->actingAs($teacher)->get("/plans/{$plan->id}/edit")->assertOk()->assertSee('goalsEditor', false);

        // duplicate year/semester rejected
        $this->actingAs($teacher)->post('/plans', $this->planFields(['student_id' => $student->id]))->assertSessionHasErrors('semester');

        // AJAX save draft
        $this->actingAs($teacher)->putJson("/plans/{$plan->id}", $this->planFields(['goals' => [
            ['bank_goal_id' => $goal->id, 'teacher_note' => 'ملاحظة'],
            ['goal_text' => 'هدف مخصص', 'domain_name' => 'مجال مخصص'],
        ]]))->assertOk()->assertJsonPath('goals_count', 2);
        $this->assertSame(2, $plan->goals()->count());

        // submit via JSON
        $this->actingAs($teacher)->postJson("/plans/{$plan->id}/submit")->assertOk()->assertJsonStructure(['redirect']);
        $this->assertSame(PlanStatus::PendingReview, $plan->fresh()->status);

        // teacher can no longer edit
        $this->actingAs($teacher)->get("/plans/{$plan->id}/edit")->assertForbidden();
        $this->actingAs($teacher)->post("/plans/{$plan->id}/approve", ['action' => 'approve'])->assertForbidden();

        // supervisor: return needs a note, then approve flow
        $this->actingAs($sup)->get("/plans/{$plan->id}")->assertOk()->assertSee('مراجعة المشرف');
        $this->actingAs($sup)->post("/plans/{$plan->id}/return", ['action' => 'return'])->assertSessionHasErrors('note');
        $this->actingAs($sup)->post("/plans/{$plan->id}/return", ['action' => 'return', 'note' => 'عدّل الأهداف'])->assertRedirect();
        $this->assertSame(PlanStatus::Returned, $plan->fresh()->status);
        $this->actingAs($teacher)->get("/plans/{$plan->id}/edit")->assertOk()->assertSee('عدّل الأهداف');

        $this->actingAs($teacher)->postJson("/plans/{$plan->id}/submit")->assertOk();
        $this->actingAs($sup)->post("/plans/{$plan->id}/approve", ['action' => 'approve'])->assertRedirect();
        $this->assertSame(PlanStatus::Approved, $plan->fresh()->status);

        $this->actingAs($teacher)->get("/plans/{$plan->id}/pdf")->assertOk()->assertHeader('Content-Type', 'application/pdf');

        // evaluation
        $this->actingAs($teacher)->post("/plans/{$plan->id}/evaluation")->assertRedirect();
        $evaluation = $plan->fresh()->evaluation;
        $this->assertNotNull($evaluation);
        $this->actingAs($teacher)->get("/evaluations/{$evaluation->id}/edit")->assertOk()->assertSee('في طور التدريب')->assertSee('غير متقن');

        $goals = $plan->goals()->get();
        $payload = ['action' => 'submit', 'evaluation_completed_at_hijri' => '1448/06/25', 'results' => $goals->map(fn ($g) => [
            'therapy_plan_goal_id' => $g->id, 'result' => 'mastered', 'note' => '',
        ])->all()];

        $this->actingAs($teacher)->put("/evaluations/{$evaluation->id}", $payload)->assertRedirect("/evaluations/{$evaluation->id}");
        $this->assertSame(EvaluationStatus::PendingReview, $evaluation->fresh()->status);

        $this->actingAs($sup)->get("/evaluations/{$evaluation->id}")->assertOk()->assertSee('تقرير التقييم');
        $this->actingAs($sup)->post("/evaluations/{$evaluation->id}/approve", ['action' => 'approve', 'performance_summary' => 'أداء جيد'])->assertRedirect();
        $this->assertSame(EvaluationStatus::Approved, $evaluation->fresh()->status);

        $this->actingAs($teacher)->get("/evaluations/{$evaluation->id}/pdf")->assertOk()->assertHeader('Content-Type', 'application/pdf');
    }

    public function test_tracking_sheet_lists_students_without_plans(): void
    {
        $sup = $this->sup();
        $t1 = $this->teacher('معلمة أ');
        $t2 = $this->teacher('معلمة ب');
        $withPlan = $this->student($t1, 'طالب لديه خطة');
        $this->student($t2, 'طالب بلا خطة');

        app(PlanWorkflowService::class)->saveDraft(new TherapyPlan, $this->planFields(['student_id' => $withPlan->id, 'teacher_id' => $t1->id]), [
            ['goal_text' => 'هدف', 'domain_name' => 'مجال'],
        ]);

        $this->actingAs($sup)->get('/tracking')->assertOk()
            ->assertSee('طالب لديه خطة')->assertSee('طالب بلا خطة')
            ->assertSee('أسماء المعلمات')->assertSee('تاريخ استلام الخطة');

        $this->actingAs($sup)->get('/tracking?plan_status=none')->assertOk()
            ->assertSee('طالب بلا خطة')->assertDontSee('طالب لديه خطة');
        $this->actingAs($sup)->get("/tracking?teacher_id={$t1->id}&academic_year=&semester=")->assertOk()
            ->assertSee('طالب لديه خطة')->assertDontSee('طالب بلا خطة');
    }
}
