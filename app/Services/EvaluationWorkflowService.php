<?php

namespace App\Services;

use App\Enums\EvaluationStatus;
use App\Enums\GoalResult;
use App\Enums\PlanStatus;
use App\Enums\ReviewAction;
use App\Exceptions\WorkflowException;
use App\Models\Evaluation;
use App\Models\TherapyPlan;
use App\Models\User;
use Illuminate\Support\Facades\DB;

/**
 * Evaluation of an APPROVED plan. Same state machine as plans, but the
 * evaluation's own status never touches the plan's status.
 */
class EvaluationWorkflowService
{
    /** Supervisor-written report columns. */
    public const REPORT_FIELDS = [
        'performance_summary',
        'strengths',
        'skills_needing_training',
        'observed_progress',
        'final_notes',
        'recommendations',
    ];

    public function __construct(private readonly PlanWorkflowService $plans) {}

    /** Starts (or returns the existing) evaluation for an approved plan. */
    public function start(TherapyPlan $plan, User $user): Evaluation
    {
        return DB::transaction(function () use ($plan, $user) {
            $lockedPlan = TherapyPlan::query()->lockForUpdate()->findOrFail($plan->getKey());

            if ($lockedPlan->status !== PlanStatus::Approved) {
                throw new WorkflowException('لا يمكن بدء التقييم قبل اعتماد الخطة.');
            }
            if (! $user->isTeacher() || (int) $lockedPlan->teacher_id !== (int) $user->id) {
                throw new WorkflowException('غير مصرّح لك بتقييم هذه الخطة.');
            }

            $evaluation = Evaluation::firstOrCreate(
                ['therapy_plan_id' => $lockedPlan->id],
                ['status' => EvaluationStatus::Draft],
            );

            foreach ($lockedPlan->goals()->get() as $goal) {
                $evaluation->goalResults()->firstOrCreate(['therapy_plan_goal_id' => $goal->id]);
            }

            return $evaluation->load('goalResults');
        });
    }

    /**
     * Partial results are fine here.
     *
     * @param  array<string,mixed>  $data  evaluation_completed_at_hijri?
     * @param  array<int,array{therapy_plan_goal_id:int|string,result?:?string,note?:?string}>  $results
     */
    public function saveDraft(Evaluation $evaluation, array $data, array $results): Evaluation
    {
        return DB::transaction(function () use ($evaluation, $data, $results) {
            $locked = $this->lock($evaluation);
            $this->assertPlanApproved($locked);

            if (! in_array($locked->status, [EvaluationStatus::Draft, EvaluationStatus::Returned], true)) {
                throw new WorkflowException('لا يمكن تعديل التقييم وهو بانتظار المراجعة أو معتمد.');
            }

            if (array_key_exists('evaluation_completed_at_hijri', $data)) {
                $locked->evaluation_completed_at_hijri = $data['evaluation_completed_at_hijri'] ?: null;
            }
            $locked->save();

            $this->syncResults($locked, $results);
            $evaluation->setRawAttributes($locked->getAttributes(), true);

            return $locked->load('goalResults');
        });
    }

    public function submit(Evaluation $evaluation, User $user): Evaluation
    {
        return DB::transaction(function () use ($evaluation, $user) {
            $locked = $this->lock($evaluation);
            $plan = $this->assertPlanApproved($locked);

            if (! $user->isTeacher() || (int) $plan->teacher_id !== (int) $user->id) {
                throw new WorkflowException('غير مصرّح لك بإرسال هذا التقييم.');
            }
            if (! in_array($locked->status, [EvaluationStatus::Draft, EvaluationStatus::Returned], true)) {
                throw new WorkflowException('لا يمكن إرسال التقييم إلا وهو مسودة أو معاد للتعديل.');
            }
            if (trim((string) $locked->evaluation_completed_at_hijri) === '') {
                throw new WorkflowException('يرجى إدخال تاريخ إتمام التقييم (هجري).');
            }

            $goalIds = $plan->goals()->pluck('id');
            if ($goalIds->isEmpty()) {
                throw new WorkflowException('لا توجد أهداف في الخطة لتقييمها.');
            }
            $rated = $locked->goalResults()->whereNotNull('result')->whereIn('therapy_plan_goal_id', $goalIds)->count();
            if ($rated < $goalIds->count()) {
                throw new WorkflowException('يجب تقييم جميع الأهداف قبل إرسال التقييم للمراجعة.');
            }

            $locked->status = EvaluationStatus::PendingReview;
            $locked->submitted_at = now();
            $locked->save();

            $this->log($locked, $user, ReviewAction::Submitted, null);
            $evaluation->setRawAttributes($locked->getAttributes(), true);

            return $locked;
        });
    }

    /**
     * Supervisor writes the report narrative while the evaluation is under review.
     *
     * @param  array<string,mixed>  $report
     */
    public function saveReport(Evaluation $evaluation, User $user, array $report): Evaluation
    {
        return DB::transaction(function () use ($evaluation, $user, $report) {
            $locked = $this->lock($evaluation);
            $this->assertSupervisor($user);
            if ($locked->status !== EvaluationStatus::PendingReview) {
                throw new WorkflowException('يمكن تعديل التقرير فقط أثناء مراجعة التقييم.');
            }

            $this->fillReport($locked, $report);
            $locked->save();
            $evaluation->setRawAttributes($locked->getAttributes(), true);

            return $locked;
        });
    }

    /** Returns the evaluation to the teacher. The plan status is NOT touched. */
    public function returnEvaluation(Evaluation $evaluation, User $user, string $note, array $report = []): Evaluation
    {
        return DB::transaction(function () use ($evaluation, $user, $note, $report) {
            $locked = $this->lock($evaluation);

            $this->assertSupervisor($user);
            if ($locked->status !== EvaluationStatus::PendingReview) {
                throw new WorkflowException('يمكن إعادة التقييم فقط وهو بانتظار المراجعة.');
            }
            if (trim($note) === '') {
                throw new WorkflowException('يرجى كتابة ملاحظة توضح سبب إعادة التقييم.');
            }

            $this->fillReport($locked, $report);
            $locked->status = EvaluationStatus::Returned;
            $locked->save();

            $this->log($locked, $user, ReviewAction::Returned, trim($note));
            $evaluation->setRawAttributes($locked->getAttributes(), true);

            return $locked;
        });
    }

    /**
     * @param  array<string,mixed>  $report  optional report fields written/updated at approval time
     */
    public function approveEvaluation(Evaluation $evaluation, User $user, ?string $note = null, array $report = []): Evaluation
    {
        return DB::transaction(function () use ($evaluation, $user, $note, $report) {
            $locked = $this->lock($evaluation);
            $plan = $this->assertPlanApproved($locked);

            $this->assertSupervisor($user);
            if ($locked->status !== EvaluationStatus::PendingReview) {
                throw new WorkflowException('يمكن اعتماد التقييم فقط وهو بانتظار المراجعة.');
            }

            $this->fillReport($locked, $report);
            $note = $note !== null && trim($note) !== '' ? trim($note) : null;

            $locked->status = EvaluationStatus::Approved;
            $locked->approved_at = now();
            $locked->approved_by = $user->id;
            $locked->locked_snapshot = $this->buildSnapshot($locked, $plan, $user, $note);
            $locked->save();

            $this->log($locked, $user, ReviewAction::Approved, $note);
            $evaluation->setRawAttributes($locked->getAttributes(), true);

            return $locked;
        });
    }

    /**
     * Frozen copy of: plan snapshot + per-goal results + report narrative.
     *
     * @return array<string,mixed>
     */
    public function buildSnapshot(Evaluation $evaluation, TherapyPlan $plan, User $approver, ?string $note = null): array
    {
        $plan->loadMissing('goals');
        $results = $evaluation->goalResults()->get()->keyBy('therapy_plan_goal_id');
        $planSnapshot = $plan->locked_snapshot ?: $this->plans->buildSnapshot($plan);

        $goals = [];
        foreach ($plan->goals as $goal) {
            $r = $results->get($goal->id);
            $goals[] = [
                'therapy_plan_goal_id' => $goal->id,
                'domain_name' => $goal->domain_name,
                'level' => $goal->level,
                'subcategory' => $goal->subcategory,
                'printed_code' => $goal->printed_code,
                'goal_text' => $goal->goal_text,
                'result' => $r?->result?->value,
                'result_label' => $r?->result?->label(),
                'note' => $r?->note,
            ];
        }

        return [
            'version' => 1,
            'frozen_at' => now()->toIso8601String(),
            'plan' => $planSnapshot,
            'evaluation' => [
                'id' => $evaluation->id,
                'evaluation_completed_at_hijri' => $evaluation->evaluation_completed_at_hijri,
                'submitted_at' => $evaluation->submitted_at?->toIso8601String(),
                'approved_at' => ($evaluation->approved_at ?? now())->toIso8601String(),
                'approval_note' => $note,
            ],
            'approver' => ['id' => $approver->id, 'name' => $approver->name],
            'results' => $goals,
            'report' => collect(self::REPORT_FIELDS)
                ->mapWithKeys(fn ($f) => [$f => $evaluation->{$f}])
                ->all(),
        ];
    }

    // ------------------------------------------------------------------

    private function lock(Evaluation $evaluation): Evaluation
    {
        return Evaluation::query()->lockForUpdate()->findOrFail($evaluation->getKey());
    }

    /** Evaluation is only meaningful while its plan is approved. */
    private function assertPlanApproved(Evaluation $evaluation): TherapyPlan
    {
        $plan = TherapyPlan::query()->findOrFail($evaluation->therapy_plan_id);
        if ($plan->status !== PlanStatus::Approved) {
            throw new WorkflowException('لا يمكن تقييم خطة غير معتمدة.');
        }

        return $plan;
    }

    private function assertSupervisor(User $user): void
    {
        if (! $user->is_active || ! $user->isSupervisor()) {
            throw new WorkflowException('هذا الإجراء متاح للمشرف فقط.');
        }
    }

    private function log(Evaluation $evaluation, User $user, ReviewAction $action, ?string $note): void
    {
        $evaluation->reviews()->create([
            'user_id' => $user->id,
            'action' => $action,
            'note' => $note,
        ]);
    }

    /** @param array<string,mixed> $report */
    private function fillReport(Evaluation $evaluation, array $report): void
    {
        foreach (self::REPORT_FIELDS as $field) {
            if (array_key_exists($field, $report)) {
                $value = $report[$field];
                $evaluation->{$field} = ($value === null || trim((string) $value) === '') ? null : trim((string) $value);
            }
        }
    }

    /** @param array<int,array<string,mixed>> $results */
    private function syncResults(Evaluation $evaluation, array $results): void
    {
        $validGoalIds = TherapyPlan::query()->findOrFail($evaluation->therapy_plan_id)
            ->goals()->pluck('id')->all();

        foreach ($results as $row) {
            $goalId = (int) ($row['therapy_plan_goal_id'] ?? 0);
            if (! in_array($goalId, $validGoalIds, true)) {
                throw new WorkflowException('أحد الأهداف المُقيَّمة لا يتبع هذه الخطة.');
            }

            $raw = $row['result'] ?? null;
            $result = $raw === null || $raw === '' ? null : ($raw instanceof GoalResult ? $raw : GoalResult::tryFrom((string) $raw));
            if ($raw !== null && $raw !== '' && $result === null) {
                throw new WorkflowException('قيمة نتيجة التقييم غير صالحة.');
            }

            $note = isset($row['note']) && trim((string) $row['note']) !== '' ? trim((string) $row['note']) : null;

            $evaluation->goalResults()->updateOrCreate(
                ['therapy_plan_goal_id' => $goalId],
                ['result' => $result, 'note' => $note],
            );
        }
    }
}
