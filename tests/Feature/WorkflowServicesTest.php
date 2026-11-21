<?php

namespace Tests\Feature;

use App\Enums\EvaluationStatus;
use App\Enums\PlanStatus;
use App\Enums\StudentPeriod;
use App\Enums\UserRole;
use App\Exceptions\WorkflowException;
use App\Models\BankGoal;
use App\Models\Evaluation;
use App\Models\GoalDomain;
use App\Models\Student;
use App\Models\TherapyPlan;
use App\Models\User;
use App\Services\EvaluationWorkflowService;
use App\Services\GoalsBankImportService;
use App\Services\PdfService;
use App\Services\PlanWorkflowService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class WorkflowServicesTest extends TestCase
{
    use RefreshDatabase;

    private function actors(): array
    {
        $teacher = User::factory()->create(['role' => UserRole::Teacher, 'is_active' => true, 'name' => 'معلمة اختبار']);
        $other = User::factory()->create(['role' => UserRole::Teacher, 'is_active' => true]);
        $sup = User::factory()->create(['role' => UserRole::Supervisor, 'is_active' => true, 'name' => 'مشرف اختبار']);
        $student = Student::create([
            'full_name' => 'طالب اختبار', 'teacher_id' => $teacher->id, 'period' => StudentPeriod::Morning,
            'birth_date_hijri' => '1440/05/12', 'diagnosis' => 'تأخر لغوي',
        ]);

        return [$teacher, $other, $sup, $student];
    }

    private function planData(): array
    {
        return [
            'academic_year' => '1447-1448', 'semester' => 'first', 'start_date_hijri' => '1447/03/01',
            'end_date_hijri' => '1447/06/01', 'sessions_count' => 20, 'sessions_per_week' => 2,
        ];
    }

    private function goals(): array
    {
        return [
            ['domain_name' => 'اللغة الاستقبالية', 'goal_text' => 'ينفذ أمراً من خطوة', 'printed_code' => '1-1'],
            ['domain_name' => 'اللغة التعبيرية', 'goal_text' => 'يسمي الأشياء', 'training_elements' => "صورة\nمجسم"],
        ];
    }

    public function test_plan_lifecycle_and_locking_rules(): void
    {
        [$teacher, $other, $sup, $student] = $this->actors();
        $svc = app(PlanWorkflowService::class);

        $plan = $svc->saveDraft(new TherapyPlan(['student_id' => $student->id, 'teacher_id' => $teacher->id]), $this->planData(), []);
        $this->assertSame(PlanStatus::Draft, $plan->status);
        $this->assertSame('طالب اختبار', $plan->student_name);

        // no goals -> cannot submit
        try {
            $svc->submit($plan, $teacher);
            $this->fail('expected failure');
        } catch (WorkflowException) {
        }

        $svc->saveDraft($plan, $this->planData(), $this->goals());
        $this->assertCount(2, $plan->fresh()->goals);

        $svc->submit($plan, $teacher);
        $plan->refresh();
        $this->assertSame(PlanStatus::PendingReview, $plan->status);

        // double submit + editing while pending are rejected
        foreach ([fn () => $svc->submit($plan, $teacher), fn () => $svc->saveDraft($plan, [], $this->goals())] as $try) {
            try {
                $try();
                $this->fail('expected WorkflowException');
            } catch (WorkflowException) {
            }
        }
    }

    public function test_full_flow_policies_and_pdfs(): void
    {
        [$teacher, $other, $sup, $student] = $this->actors();
        $plans = app(PlanWorkflowService::class);
        $evals = app(EvaluationWorkflowService::class);
        $pdf = app(PdfService::class);

        $plan = $plans->saveDraft(new TherapyPlan(['student_id' => $student->id, 'teacher_id' => $teacher->id]), $this->planData(), $this->goals());

        // policies
        $this->assertTrue($teacher->can('update', $plan));
        $this->assertFalse($other->can('view', $plan));
        $this->assertFalse($other->can('view', $student));
        $this->assertTrue($teacher->can('view', $student));
        $this->assertTrue($sup->can('view', $plan));
        $this->assertFalse($sup->can('update', $plan));
        $this->assertFalse($teacher->can('create', [Evaluation::class, $plan]));

        $plans->submit($plan, $teacher);
        $this->assertFalse($teacher->can('update', $plan->fresh()));
        $this->assertTrue($sup->can('review', $plan->fresh()));

        // PDF refused before approval
        try {
            $pdf->planPdf($plan->fresh());
            $this->fail('pdf must be refused');
        } catch (WorkflowException) {
        }

        // return -> edit -> resubmit -> approve
        $plans->returnPlan($plan, $sup, 'عدّل الأهداف');
        $this->assertSame(PlanStatus::Returned, $plan->fresh()->status);
        $plans->saveDraft($plan, [], $this->goals());
        $plans->submit($plan, $teacher);
        $plans->approve($plan, $sup, 'ممتاز');
        $plan->refresh();
        $this->assertSame(PlanStatus::Approved, $plan->status);
        $this->assertSame(2, count($plan->locked_snapshot['goals']));
        $this->assertCount(4, $plan->reviews);

        $bin = $pdf->planPdf($plan);
        $this->assertStringStartsWith('%PDF', $bin);
        file_put_contents('/tmp/plan_test.pdf', $bin);

        // evaluation
        $this->assertTrue($teacher->can('create', [Evaluation::class, $plan]));
        $eval = $evals->start($plan, $teacher);
        $goalIds = $plan->goals()->pluck('id')->all();

        $evals->saveDraft($eval, ['evaluation_completed_at_hijri' => '1447/06/05'], [
            ['therapy_plan_goal_id' => $goalIds[0], 'result' => 'mastered'],
        ]);
        try {
            $evals->submit($eval, $teacher);
            $this->fail('partial results must not submit');
        } catch (WorkflowException) {
        }

        $evals->saveDraft($eval, [], [
            ['therapy_plan_goal_id' => $goalIds[1], 'result' => 'in_training', 'note' => 'يحتاج دعماً'],
        ]);
        $evals->submit($eval, $teacher);
        $eval->refresh();
        $this->assertSame(EvaluationStatus::PendingReview, $eval->status);

        $evals->returnEvaluation($eval, $sup, 'راجع الملاحظات');
        $this->assertSame(EvaluationStatus::Returned, $eval->fresh()->status);
        $this->assertSame(PlanStatus::Approved, $plan->fresh()->status); // plan untouched

        $evals->submit($eval, $teacher);
        $evals->approveEvaluation($eval, $sup, null, [
            'performance_summary' => "أظهر الطالب تحسناً.\nسطر ثانٍ",
            'strengths' => 'الانتباه', 'skills_needing_training' => 'التسمية',
            'observed_progress' => 'تقدم جيد', 'recommendations' => 'المتابعة المنزلية',
        ]);
        $eval->refresh();
        $this->assertSame(EvaluationStatus::Approved, $eval->status);
        $this->assertSame('mastered', $eval->locked_snapshot['results'][0]['result']);
        $this->assertSame('الانتباه', $eval->locked_snapshot['report']['strengths']);

        $bin = $pdf->evaluationReportPdf($eval);
        $this->assertStringStartsWith('%PDF', $bin);
        file_put_contents('/tmp/evaluation_test.pdf', $bin);
        $this->assertSame(200, $pdf->evaluationReportPdfResponse($eval)->getStatusCode());
    }

    public function test_goals_bank_import_is_idempotent(): void
    {
        $path = base_path(config('speech.goals_bank_path'));
        $svc = app(GoalsBankImportService::class);

        $first = $svc->import($path);
        $domains = GoalDomain::count();
        $goals = BankGoal::count();
        $this->assertGreaterThan(0, $goals);
        $this->assertSame($first['goals_created'], $goals);

        $second = $svc->import($path);
        $this->assertSame($domains, GoalDomain::count());
        $this->assertSame($goals, BankGoal::count());
        $this->assertSame(0, $second['goals_created']);
        $this->assertSame(0, $second['goals_updated']);
    }

    public function test_plan_goal_edits_do_not_affect_bank_or_other_plans(): void
    {
        [$teacher, , , $student] = $this->actors();
        $domain = GoalDomain::create(['name' => 'اللغة الاستقبالية', 'sort_order' => 1]);
        $bank = BankGoal::create([
            'uuid' => (string) \Illuminate\Support\Str::uuid(),
            'goal_domain_id' => $domain->id,
            'printed_code' => '1-99',
            'goal_text' => 'نص الهدف الأصلي في البنك',
            'training_elements' => 'تدريب أصلي',
            'fingerprint' => 'fp-isolation-'.uniqid(),
        ]);

        $plans = app(PlanWorkflowService::class);
        $planA = $plans->saveDraft(new TherapyPlan(['student_id' => $student->id, 'teacher_id' => $teacher->id]), $this->planData(), [
            [
                'bank_goal_id' => $bank->id,
                'domain_name' => $domain->name,
                'printed_code' => $bank->printed_code,
                'goal_text' => $bank->goal_text,
                'training_elements' => $bank->training_elements,
            ],
        ]);
        $studentB = Student::create([
            'full_name' => 'طالب آخر', 'teacher_id' => $teacher->id, 'period' => StudentPeriod::Evening,
            'birth_date_hijri' => '1441/01/01',
        ]);
        $planB = $plans->saveDraft(new TherapyPlan(['student_id' => $studentB->id, 'teacher_id' => $teacher->id]), $this->planData(), [
            [
                'bank_goal_id' => $bank->id,
                'domain_name' => $domain->name,
                'printed_code' => $bank->printed_code,
                'goal_text' => $bank->goal_text,
            ],
        ]);

        $plans->saveDraft($planA, [], [
            [
                'bank_goal_id' => $bank->id,
                'domain_name' => $domain->name,
                'printed_code' => $bank->printed_code,
                'goal_text' => 'صياغة معدّلة للطالب أ فقط',
                'teacher_note' => 'ملاحظة خاصة',
            ],
        ]);

        $this->assertSame('نص الهدف الأصلي في البنك', $bank->fresh()->goal_text);
        $this->assertSame('تدريب أصلي', $bank->fresh()->training_elements);
        $this->assertSame('صياغة معدّلة للطالب أ فقط', $planA->fresh()->goals->first()->goal_text);
        $this->assertSame('نص الهدف الأصلي في البنك', $planB->fresh()->goals->first()->goal_text);

        $plans->saveDraft($planA, [], []);
        $this->assertCount(0, $planA->fresh()->goals);
        $this->assertCount(1, $planB->fresh()->goals);
        $this->assertDatabaseHas('bank_goals', ['id' => $bank->id, 'goal_text' => 'نص الهدف الأصلي في البنك']);
    }

    public function test_approved_snapshot_survives_student_data_changes(): void
    {
        [$teacher, , $sup, $student] = $this->actors();
        $plans = app(PlanWorkflowService::class);
        $pdf = app(PdfService::class);

        $plan = $plans->saveDraft(
            new TherapyPlan(['student_id' => $student->id, 'teacher_id' => $teacher->id]),
            $this->planData(),
            $this->goals()
        );
        $plans->submit($plan, $teacher);
        $plans->approve($plan, $sup);
        $plan->refresh();

        $this->assertSame('طالب اختبار', $plan->locked_snapshot['student']['full_name']);
        $this->assertSame('معلمة اختبار', $plan->locked_snapshot['teacher']['name']);

        $student->update(['full_name' => 'اسم جديد بعد الاعتماد', 'diagnosis' => 'تشخيص جديد']);
        $teacher->update(['name' => 'معلمة تغيّر اسمها']);

        $plan->refresh();
        $this->assertSame('طالب اختبار', $plan->locked_snapshot['student']['full_name']);
        $this->assertSame('معلمة اختبار', $plan->locked_snapshot['teacher']['name']);
        $this->assertSame('طالب اختبار', $plan->student_name);
        $this->assertSame('معلمة اختبار', $plan->teacher_name);

        $bin = $pdf->planPdf($plan);
        $this->assertStringStartsWith('%PDF', $bin);
        $this->assertSame('اسم جديد بعد الاعتماد', $student->fresh()->full_name);
        $this->assertSame('طالب اختبار', $plan->fresh()->locked_snapshot['student']['full_name']);
    }
}
