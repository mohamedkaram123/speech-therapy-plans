<?php

namespace App\Policies;

use App\Enums\PlanStatus;
use App\Models\TherapyPlan;
use App\Models\User;

class TherapyPlanPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->is_active;
    }

    public function view(User $user, TherapyPlan $plan): bool
    {
        return $user->is_active && ($user->isSupervisor() || $this->owns($user, $plan));
    }

    /** Only teachers create plans (for their own students; checked in StorePlanRequest). */
    public function create(User $user): bool
    {
        return $user->is_active && $user->isTeacher();
    }

    /** Teacher edits own plan only while it is a draft or returned. */
    public function update(User $user, TherapyPlan $plan): bool
    {
        return $user->is_active
            && $user->isTeacher()
            && $this->owns($user, $plan)
            && in_array($plan->status, [PlanStatus::Draft, PlanStatus::Returned], true);
    }

    public function submit(User $user, TherapyPlan $plan): bool
    {
        return $this->update($user, $plan);
    }

    /** Teacher may delete own drafts only (never-submitted work). */
    public function delete(User $user, TherapyPlan $plan): bool
    {
        return $user->is_active
            && $user->isTeacher()
            && $this->owns($user, $plan)
            && $plan->status === PlanStatus::Draft;
    }

    /** Supervisor reviews plans waiting for review. */
    public function review(User $user, TherapyPlan $plan): bool
    {
        return $user->is_active
            && $user->isSupervisor()
            && $plan->status === PlanStatus::PendingReview;
    }

    public function approve(User $user, TherapyPlan $plan): bool
    {
        return $this->review($user, $plan);
    }

    public function return(User $user, TherapyPlan $plan): bool
    {
        return $this->review($user, $plan);
    }

    /** Printing/export is only offered for approved plans the user can see. */
    public function download(User $user, TherapyPlan $plan): bool
    {
        return $this->view($user, $plan) && $plan->status === PlanStatus::Approved;
    }

    private function owns(User $user, TherapyPlan $plan): bool
    {
        return (int) $plan->teacher_id === (int) $user->id;
    }
}
