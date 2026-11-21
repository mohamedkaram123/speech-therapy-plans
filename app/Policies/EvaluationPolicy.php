<?php

namespace App\Policies;

use App\Enums\EvaluationStatus;
use App\Enums\PlanStatus;
use App\Models\Evaluation;
use App\Models\TherapyPlan;
use App\Models\User;

class EvaluationPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->is_active;
    }

    public function view(User $user, Evaluation $evaluation): bool
    {
        if (! $user->is_active) {
            return false;
        }

        return $user->isSupervisor() || $this->owns($user, $evaluation->therapyPlan);
    }

    /**
     * Teacher may start an evaluation only for an own, approved plan
     * that does not have one yet. Usage: $user->can('create', [Evaluation::class, $plan]).
     */
    public function create(User $user, ?TherapyPlan $plan = null): bool
    {
        if (! $user->is_active || ! $user->isTeacher()) {
            return false;
        }

        if ($plan === null) {
            return true; // coarse check (e.g. menu visibility)
        }

        return $this->owns($user, $plan)
            && $plan->status === PlanStatus::Approved
            && ! $plan->evaluation()->exists();
    }

    /** Teacher edits own draft/returned evaluation, and only for an approved plan. */
    public function update(User $user, Evaluation $evaluation): bool
    {
        $plan = $evaluation->therapyPlan;

        return $user->is_active
            && $user->isTeacher()
            && $this->owns($user, $plan)
            && $plan->status === PlanStatus::Approved
            && in_array($evaluation->status, [EvaluationStatus::Draft, EvaluationStatus::Returned], true);
    }

    public function submit(User $user, Evaluation $evaluation): bool
    {
        return $this->update($user, $evaluation);
    }

    public function delete(User $user, Evaluation $evaluation): bool
    {
        return $user->is_active
            && $user->isTeacher()
            && $this->owns($user, $evaluation->therapyPlan)
            && $evaluation->status === EvaluationStatus::Draft;
    }

    public function review(User $user, Evaluation $evaluation): bool
    {
        return $user->is_active
            && $user->isSupervisor()
            && $evaluation->status === EvaluationStatus::PendingReview;
    }

    public function approve(User $user, Evaluation $evaluation): bool
    {
        return $this->review($user, $evaluation);
    }

    public function return(User $user, Evaluation $evaluation): bool
    {
        return $this->review($user, $evaluation);
    }

    public function download(User $user, Evaluation $evaluation): bool
    {
        return $this->view($user, $evaluation) && $evaluation->status === EvaluationStatus::Approved;
    }

    private function owns(User $user, TherapyPlan $plan): bool
    {
        return (int) $plan->teacher_id === (int) $user->id;
    }
}
