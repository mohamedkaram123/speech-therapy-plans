<?php

namespace App\Http\Controllers;

use App\Enums\PlanStatus;
use App\Enums\ReviewAction;
use App\Enums\Semester;
use App\Enums\StudentPeriod;
use App\Enums\UserRole;
use App\Models\Student;
use App\Models\TherapyPlan;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\View\View;

/** Plan receipt sheet (كشف استلام خطط): every student, with or without a plan. */
class TrackingController extends Controller
{
    public function index(Request $request): View
    {
        $this->authorize('view-tracking');

        $latest = TherapyPlan::query()->latest('id')->first();

        $year = $request->has('academic_year')
            ? (string) $request->input('academic_year')
            : (string) ($latest?->academic_year ?? '');
        $semester = $request->has('semester')
            ? Semester::tryFrom((string) $request->input('semester'))
            : $latest?->semester;
        $period = StudentPeriod::tryFrom((string) $request->input('period'));
        $teacherId = $request->integer('teacher_id') ?: null;
        $statusFilter = (string) $request->input('plan_status', '');
        $statusFilter = ($statusFilter === 'none' || PlanStatus::tryFrom($statusFilter)) ? $statusFilter : '';

        $students = Student::query()
            ->with('teacher')
            ->when($period, fn ($q) => $q->where('period', $period->value))
            ->when($teacherId, fn ($q) => $q->where('teacher_id', $teacherId))
            ->get();

        $plans = TherapyPlan::query()
            ->with(['reviews'])
            ->whereIn('student_id', $students->pluck('id'))
            ->when($year !== '', fn ($q) => $q->where('academic_year', $year))
            ->when($semester, fn ($q) => $q->where('semester', $semester->value))
            ->get()
            ->groupBy('student_id');

        $rows = collect();
        foreach ($students as $student) {
            $studentPlans = $plans->get($student->id, collect());
            if ($studentPlans->isEmpty()) {
                $rows->push(['student' => $student, 'plan' => null]);
            } else {
                foreach ($studentPlans as $plan) {
                    $rows->push(['student' => $student, 'plan' => $plan]);
                }
            }
        }

        if ($statusFilter === 'none') {
            $rows = $rows->filter(fn ($r) => $r['plan'] === null);
        } elseif ($statusFilter !== '') {
            $rows = $rows->filter(fn ($r) => $r['plan']?->status?->value === $statusFilter);
        }

        $rows = $rows
            ->sortBy(fn ($r) => [$r['student']->teacher?->name ?? '', $r['student']->full_name])
            ->values()
            ->map(function ($r) {
                $plan = $r['plan'];
                $r['note'] = null;
                if ($plan) {
                    $r['note'] = $plan->reviews
                        ->filter(fn ($rv) => $rv->action !== ReviewAction::Submitted && $rv->note)
                        ->last()?->note;
                }

                return $r;
            });

        // Sections by student period (morning / evening) as in the printed sheet.
        $sections = $rows->groupBy(fn ($r) => $r['student']->period->value);

        return view('tracking.index', [
            'sections' => collect(StudentPeriod::cases())
                ->mapWithKeys(fn (StudentPeriod $p) => [$p->value => ['label' => $p->label(), 'rows' => $sections->get($p->value, collect())]])
                ->filter(fn ($s) => $s['rows']->isNotEmpty()),
            'total' => $rows->count(),
            'received' => $rows->filter(fn ($r) => $r['plan']?->submitted_at !== null)->count(),
            'filters' => ['academic_year' => $year, 'semester' => $semester?->value, 'period' => $period?->value, 'teacher_id' => $teacherId, 'plan_status' => $statusFilter],
            'years' => TherapyPlan::query()->distinct()->orderByDesc('academic_year')->pluck('academic_year'),
            'teachers' => User::where('role', UserRole::Teacher->value)->orderBy('name')->get(['id', 'name']),
        ]);
    }
}
