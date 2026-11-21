<?php

namespace App\Services;

use App\Enums\PlanStatus;
use App\Enums\ReviewAction;
use App\Exceptions\WorkflowException;
use App\Models\BankGoal;
use App\Models\Student;
use App\Models\TherapyPlan;
use App\Models\TherapyPlanGoal;
use App\Models\User;
use Illuminate\Support\Facades\DB;

/**
 * draft ──submit──▶ pending_review ──approve──▶ approved (frozen)
 *   ▲                     │
 *   └──── returned ◀──────┘
 *
 * Every transition runs inside a transaction and re-reads the plan with
 * lockForUpdate(), so a double-click / concurrent request sees the new status
 * and fails instead of applying the transition twice.
 */
class PlanWorkflowService
{
    /** Plan columns a teacher may set through saveDraft(). */
    private const PLAN_FIELDS = [
        'academic_year',
        'semester',
        'start_date_hijri',
        'end_date_hijri',
        'sessions_count',
        'sessions_per_week',
    ];

    /**
     * Create (if not persisted) or update a plan and replace its goals.
     *
     * @param  array<string,mixed>  $data  plan fields (+ student_id / teacher_id when creating)
     * @param  array<int,array<string,mixed>>  $goals  each: bank_goal_id? | domain_name, goal_text, level, subcategory,
     *                                                 printed_code, training_elements, generalization, teacher_note
     */
    public function saveDraft(TherapyPlan $plan, array $data, array $goals): TherapyPlan
    {
        return DB::transaction(function () use ($plan, $data, $goals) {
            if ($plan->exists) {
                $locked = $this->lock($plan);
                if (! in_array($locked->status, [PlanStatus::Draft, PlanStatus::Returned], true)) {
                    throw new WorkflowException('لا يمكن تعديل الخطة وهي بانتظار المراجعة أو معتمدة.');
                }
            } else {
                $locked = $plan;
                $locked->fill(array_intersect_key($data, array_flip(['student_id', 'teacher_id'])));
                $locked->status = PlanStatus::Draft;
            }

            $locked->fill(array_intersect_key($data, array_flip(self::PLAN_FIELDS)));
            $this->refreshIdentitySnapshot($locked);
            $locked->save();

            $this->replaceGoals($locked, $goals);

            if ($locked !== $plan) {
                $plan->setRawAttributes($locked->getAttributes(), true);
            }

            return $locked->load('goals');
        });
    }

    public function submit(TherapyPlan $plan, User $user): TherapyPlan
    {
        return DB::transaction(function () use ($plan, $user) {
            $locked = $this->lock($plan);

            if (! $user->isTeacher() || (int) $locked->teacher_id !== (int) $user->id) {
                throw new WorkflowException('غير مصرّح لك بإرسال هذه الخطة.');
            }
            if (! in_array($locked->status, [PlanStatus::Draft, PlanStatus::Returned], true)) {
                throw new WorkflowException('لا يمكن إرسال الخطة إلا وهي مسودة أو معادة للتعديل.');
            }
            if ($locked->goals()->count() < 1) {
                throw new WorkflowException('يجب إضافة هدف واحد على الأقل قبل إرسال الخطة للمراجعة.');
            }
            foreach (self::PLAN_FIELDS as $field) {
                if ($locked->{$field} === null || $locked->{$field} === '') {
                    throw new WorkflowException('يرجى استكمال بيانات الخطة قبل إرسالها للمراجعة.');
                }
            }

            $this->refreshIdentitySnapshot($locked);
            $locked->status = PlanStatus::PendingReview;
            $locked->submitted_at = now();
            $locked->save();

            $this->log($locked, $user, ReviewAction::Submitted, null);
            $plan->setRawAttributes($locked->getAttributes(), true);

            return $locked;
        });
    }

    public function returnPlan(TherapyPlan $plan, User $user, string $note): TherapyPlan
    {
        return DB::transaction(function () use ($plan, $user, $note) {
            $locked = $this->lock($plan);

            $this->assertSupervisor($user);
            if ($locked->status !== PlanStatus::PendingReview) {
                throw new WorkflowException('يمكن إعادة الخطة فقط وهي بانتظار المراجعة.');
            }
            if (trim($note) === '') {
                throw new WorkflowException('يرجى كتابة ملاحظة توضح سبب إعادة الخطة.');
            }

            $locked->status = PlanStatus::Returned;
            $locked->save();

            $this->log($locked, $user, ReviewAction::Returned, trim($note));
            $plan->setRawAttributes($locked->getAttributes(), true);

            return $locked;
        });
    }

    public function approve(TherapyPlan $plan, User $user, ?string $note = null): TherapyPlan
    {
        return DB::transaction(function () use ($plan, $user, $note) {
            $locked = $this->lock($plan);

            $this->assertSupervisor($user);
            if ($locked->status !== PlanStatus::PendingReview) {
                throw new WorkflowException('يمكن اعتماد الخطة فقط وهي بانتظار المراجعة.');
            }
            if ($locked->goals()->count() < 1) {
                throw new WorkflowException('لا يمكن اعتماد خطة بلا أهداف.');
            }

            $note = $note !== null && trim($note) !== '' ? trim($note) : null;

            $locked->status = PlanStatus::Approved;
            $locked->approved_at = now();
            $locked->approved_by = $user->id;
            $locked->locked_snapshot = $this->buildSnapshot($locked, $user, $note);
            $locked->save();

            $this->log($locked, $user, ReviewAction::Approved, $note);
            $plan->setRawAttributes($locked->getAttributes(), true);

            return $locked;
        });
    }

    /**
     * Plain-array copy of everything printed on the plan: student, teacher,
     * plan fields and goals. Stored in locked_snapshot on approval and reused
     * by the evaluation snapshot.
     *
     * @return array<string,mixed>
     */
    public function buildSnapshot(TherapyPlan $plan, ?User $approver = null, ?string $approvalNote = null): array
    {
        $plan->loadMissing('goals');
        $approver ??= $plan->approver;

        return [
            'version' => 1,
            'frozen_at' => now()->toIso8601String(),
            'plan' => [
                'id' => $plan->id,
                'academic_year' => $plan->academic_year,
                'semester' => $plan->semester?->value,
                'semester_label' => $plan->semester?->label(),
                'start_date_hijri' => $plan->start_date_hijri,
                'end_date_hijri' => $plan->end_date_hijri,
                'sessions_count' => $plan->sessions_count,
                'sessions_per_week' => $plan->sessions_per_week,
                'submitted_at' => $plan->submitted_at?->toIso8601String(),
                'approved_at' => ($plan->approved_at ?? now())->toIso8601String(),
                'approval_note' => $approvalNote,
            ],
            'student' => [
                'id' => $plan->student_id,
                'full_name' => $plan->student_name,
                'birth_date_hijri' => $plan->birth_date_hijri,
                'period' => $plan->period?->value,
                'period_label' => $plan->period?->label(),
                'diagnosis' => $plan->diagnosis,
            ],
            'teacher' => [
                'id' => $plan->teacher_id,
                'name' => $plan->teacher_name,
            ],
            'approver' => $approver ? ['id' => $approver->id, 'name' => $approver->name] : null,
            'goals' => $plan->goals->map(fn (TherapyPlanGoal $g) => [
                'id' => $g->id,
                'bank_goal_id' => $g->bank_goal_id,
                'domain_name' => $g->domain_name,
                'level' => $g->level,
                'subcategory' => $g->subcategory,
                'printed_code' => $g->printed_code,
                'goal_text' => $g->goal_text,
                'training_elements' => $g->training_elements,
                'generalization' => $g->generalization,
                'teacher_note' => $g->teacher_note,
                'sort_order' => $g->sort_order,
            ])->values()->all(),
        ];
    }

    // ------------------------------------------------------------------

    private function lock(TherapyPlan $plan): TherapyPlan
    {
        return TherapyPlan::query()->lockForUpdate()->findOrFail($plan->getKey());
    }

    private function assertSupervisor(User $user): void
    {
        if (! $user->is_active || ! $user->isSupervisor()) {
            throw new WorkflowException('هذا الإجراء متاح للمشرف فقط.');
        }
    }

    private function log(TherapyPlan $plan, User $user, ReviewAction $action, ?string $note): void
    {
        $plan->reviews()->create([
            'user_id' => $user->id,
            'action' => $action,
            'note' => $note,
        ]);
    }

    /** Copies student/teacher identity onto the plan row (editable states only). */
    private function refreshIdentitySnapshot(TherapyPlan $plan): void
    {
        $student = $plan->student_id ? Student::withTrashed()->find($plan->student_id) : null;
        $teacher = $plan->teacher_id ? User::find($plan->teacher_id) : null;

        if ($student === null) {
            throw new WorkflowException('الطالب غير موجود.');
        }

        $plan->student_name = $student->full_name;
        $plan->birth_date_hijri = $student->birth_date_hijri;
        $plan->period = $student->period;
        $plan->diagnosis = $student->diagnosis;

        if ($teacher !== null) {
            $plan->teacher_name = $teacher->name;
        }
    }

    /** @param array<int,array<string,mixed>> $goals */
    private function replaceGoals(TherapyPlan $plan, array $goals): void
    {
        $plan->goals()->delete();

        $bank = BankGoal::with('domain')
            ->whereIn('id', collect($goals)->pluck('bank_goal_id')->filter()->all())
            ->get()
            ->keyBy('id');

        $order = 0;
        foreach (array_values($goals) as $g) {
            $b = isset($g['bank_goal_id']) ? $bank->get($g['bank_goal_id']) : null;

            $goalText = $g['goal_text'] ?? $b?->goal_text;
            if ($goalText === null || trim((string) $goalText) === '') {
                throw new WorkflowException('نص الهدف مطلوب لكل هدف في الخطة.');
            }

            $plan->goals()->create([
                'bank_goal_id' => $b?->id,
                'domain_name' => $g['domain_name'] ?? $b?->domain?->name ?? 'أخرى',
                'level' => $g['level'] ?? $b?->level,
                'subcategory' => $g['subcategory'] ?? $b?->subcategory,
                'printed_code' => $g['printed_code'] ?? $b?->printed_code,
                'goal_text' => $goalText,
                'training_elements' => $g['training_elements'] ?? $b?->training_elements,
                'generalization' => $g['generalization'] ?? $b?->generalization,
                'teacher_note' => $g['teacher_note'] ?? null,
                'sort_order' => ++$order,
            ]);
        }
    }
}
