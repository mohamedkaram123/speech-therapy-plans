<?php

namespace App\Http\Controllers;

use App\Enums\EvaluationStatus;
use App\Enums\PlanStatus;
use App\Enums\UserRole;
use App\Models\Evaluation;
use App\Models\Student;
use App\Models\TherapyPlan;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function __invoke(Request $request): View
    {
        $user = $request->user();
        $this->authorize('viewAny', TherapyPlan::class);

        return $user->isSupervisor() ? $this->supervisor() : $this->teacher($user);
    }

    private function supervisor(): View
    {
        $pendingPlans = TherapyPlan::with('student')
            ->where('status', PlanStatus::PendingReview)
            ->orderBy('submitted_at')
            ->get();

        $pendingEvaluations = Evaluation::with('therapyPlan')
            ->where('status', EvaluationStatus::PendingReview)
            ->orderBy('submitted_at')
            ->get();

        $stats = [
            'teachers' => User::where('role', UserRole::Teacher->value)->where('is_active', true)->count(),
            'students' => Student::count(),
            'pending_plans' => $pendingPlans->count(),
            'pending_evaluations' => $pendingEvaluations->count(),
            'approved_plans' => TherapyPlan::where('status', PlanStatus::Approved)->count(),
            'returned_plans' => TherapyPlan::where('status', PlanStatus::Returned)->count(),
        ];

        return view('dashboard.supervisor', [
            'stats' => $stats,
            'pendingPlans' => $pendingPlans->take(10),
            'pendingEvaluations' => $pendingEvaluations->take(10),
        ]);
    }

    private function teacher(User $user): View
    {
        $plans = TherapyPlan::with(['student', 'evaluation', 'reviews'])
            ->where('teacher_id', $user->id)
            ->latest('updated_at')
            ->get();

        $students = Student::where('teacher_id', $user->id)->orderBy('full_name')->get();
        $studentsWithoutPlan = $students->filter(fn (Student $s) => ! $plans->contains('student_id', $s->id));

        return view('dashboard.teacher', [
            'studentsCount' => $students->count(),
            'studentsWithoutPlan' => $studentsWithoutPlan,
            'returnedPlans' => $plans->where('status', PlanStatus::Returned),
            'draftPlans' => $plans->where('status', PlanStatus::Draft),
            'pendingPlans' => $plans->where('status', PlanStatus::PendingReview),
            'approvedWithoutEvaluation' => $plans->filter(
                fn (TherapyPlan $p) => $p->status === PlanStatus::Approved && $p->evaluation === null
            ),
            'openEvaluations' => $plans->filter(
                fn (TherapyPlan $p) => $p->evaluation !== null
                    && in_array($p->evaluation->status, [EvaluationStatus::Draft, EvaluationStatus::Returned], true)
            ),
        ]);
    }
}
