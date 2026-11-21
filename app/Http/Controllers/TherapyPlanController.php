<?php

namespace App\Http\Controllers;

use App\Enums\PlanStatus;
use App\Enums\Semester;
use App\Exceptions\WorkflowException;
use App\Http\Requests\ReviewPlanRequest;
use App\Http\Requests\StorePlanRequest;
use App\Http\Requests\UpdatePlanGoalsRequest;
use App\Models\GoalDomain;
use App\Models\Student;
use App\Models\TherapyPlan;
use App\Models\TherapyPlanGoal;
use App\Services\PlanWorkflowService;
use App\Support\HijriDate;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class TherapyPlanController extends Controller
{
    public function __construct(private readonly PlanWorkflowService $workflow) {}

    public function index(Request $request): View
    {
        $this->authorize('viewAny', TherapyPlan::class);
        $user = $request->user();

        $plans = TherapyPlan::query()
            ->with(['student', 'evaluation'])
            ->withCount('goals')
            ->when($user->isTeacher(), fn ($q) => $q->where('teacher_id', $user->id))
            ->when($request->filled('status') && PlanStatus::tryFrom((string) $request->input('status')),
                fn ($q) => $q->where('status', $request->input('status')))
            ->when($request->filled('q'), fn ($q) => $q->where(function ($q) use ($request) {
                $term = '%'.trim($request->string('q')).'%';
                $q->where('student_name', 'like', $term)->orWhere('teacher_name', 'like', $term);
            }))
            ->latest('updated_at')
            ->paginate(20)
            ->withQueryString();

        return view('plans.index', compact('plans'));
    }

    public function create(Request $request, Student $student): View|RedirectResponse
    {
        $this->authorize('create', TherapyPlan::class);
        $this->authorize('view', $student);

        if (! $student->teacher_id) {
            return redirect()
                ->route('students.edit', $student)
                ->with('error', 'عيّن المعلمة المسؤولة عن الطالب أولاً قبل إنشاء الخطة.');
        }

        $today = Carbon::today();
        $hijri = HijriDate::todayHijri();
        [$y, $m] = array_map('intval', array_pad(explode('/', $hijri), 2, 0));
        $startYear = $m >= 2 ? $y : $y - 1; // academic year starts around Safar

        return view('plans.create', [
            'student' => $student->load('teacher'),
            'defaults' => [
                'academic_year' => $student->academic_year ?: ($startYear.'-'.($startYear + 1)),
                'start_date_hijri' => $hijri,
                'semester' => $student->semester?->value ?: ($m >= 2 && $m <= 7 ? 'first' : 'second'),
            ],
            'semesters' => Semester::cases(),
            'today' => $today,
        ]);
    }

    public function store(StorePlanRequest $request): RedirectResponse
    {
        $this->authorize('create', TherapyPlan::class);
        $student = Student::findOrFail($request->integer('student_id'));
        $this->authorize('view', $student);

        if (! $student->teacher_id) {
            return redirect()
                ->route('students.edit', $student)
                ->with('error', 'عيّن المعلمة المسؤولة عن الطالب أولاً قبل إنشاء الخطة.');
        }

        // If the student has no period yet, save the one chosen on the plan form.
        if (! $student->period && $request->filled('period')) {
            $student->update(['period' => $request->input('period')]);
            $student->refresh();
        }

        if (! $student->period) {
            return back()->withInput()->with('error', 'حدد فترة الطالب (صباحي/مسائي) قبل إنشاء الخطة.');
        }

        try {
            $plan = $this->workflow->saveDraft(new TherapyPlan, [
                ...$request->safe()->except(['student_id', 'period']),
                'student_id' => $student->id,
                'teacher_id' => $student->teacher_id,
            ], []);
        } catch (WorkflowException $e) {
            return back()->withInput()->with('error', $e->getMessage());
        }

        return redirect()->route('plans.edit', $plan)->with('success', 'تم إنشاء مسودة الخطة. أضف الأهداف من بنك الأهداف.');
    }

    public function show(TherapyPlan $plan): View
    {
        $this->authorize('view', $plan);

        $plan->load(['student', 'teacher', 'approver', 'evaluation', 'reviews.user', 'goals']);

        return view('plans.show', [
            'plan' => $plan,
            'goalsByDomain' => $plan->goals->groupBy('domain_name'),
            'lastReturn' => $plan->reviews->where('action', \App\Enums\ReviewAction::Returned)->last(),
        ]);
    }

    /** Goals editor page. */
    public function edit(TherapyPlan $plan): View
    {
        $this->authorize('update', $plan);

        $plan->load(['student', 'goals', 'reviews.user']);

        $config = [
            'saveUrl' => route('plans.update', $plan),
            'submitUrl' => route('plans.submit', $plan),
            'searchUrl' => route('goals-bank.search'),
            'csrf' => csrf_token(),
            'plan' => [
                'academic_year' => $plan->academic_year,
                'semester' => $plan->semester?->value,
                'start_date_hijri' => $plan->start_date_hijri,
                'end_date_hijri' => $plan->end_date_hijri,
                'sessions_count' => $plan->sessions_count,
                'sessions_per_week' => $plan->sessions_per_week,
            ],
            'goals' => $plan->goals->map(fn (TherapyPlanGoal $g) => [
                'bank_goal_id' => $g->bank_goal_id,
                'domain_name' => $g->domain_name,
                'level' => $g->level,
                'subcategory' => $g->subcategory,
                'printed_code' => $g->printed_code,
                'goal_text' => $g->goal_text,
                'training_elements' => $g->training_elements,
                'generalization' => $g->generalization,
                'teacher_note' => $g->teacher_note,
            ])->values(),
            'domains' => GoalDomain::orderBy('sort_order')->orderBy('id')->get(['id', 'name']),
        ];

        return view('plans.edit', [
            'plan' => $plan,
            'config' => $config,
            'semesters' => Semester::cases(),
            'lastReturn' => $plan->reviews->where('action', \App\Enums\ReviewAction::Returned)->last(),
        ]);
    }

    /** Saves plan fields + the full goals list (JSON from the editor, or a plain form post). */
    public function update(UpdatePlanGoalsRequest $request, TherapyPlan $plan): JsonResponse|RedirectResponse
    {
        $this->authorize('update', $plan);

        try {
            $this->workflow->saveDraft($plan, $request->planData(), $request->goalsData());
        } catch (WorkflowException $e) {
            return $this->failure($request, $e);
        }

        if ($request->expectsJson()) {
            return response()->json([
                'message' => 'تم حفظ المسودة.',
                'saved_at' => now()->format('H:i'),
                'goals_count' => $plan->goals()->count(),
            ]);
        }

        return back()->with('success', 'تم حفظ المسودة.');
    }

    public function submit(Request $request, TherapyPlan $plan): JsonResponse|RedirectResponse
    {
        $this->authorize('submit', $plan);

        try {
            $this->workflow->submit($plan, $request->user());
        } catch (WorkflowException $e) {
            return $this->failure($request, $e);
        }

        $message = 'تم إرسال الخطة للمراجعة.';
        if ($request->expectsJson()) {
            session()->flash('success', $message);

            return response()->json(['message' => $message, 'redirect' => route('plans.show', $plan)]);
        }

        return redirect()->route('plans.show', $plan)->with('success', $message);
    }

    public function returnPlan(ReviewPlanRequest $request, TherapyPlan $plan): RedirectResponse
    {
        $this->authorize('return', $plan);

        try {
            $this->workflow->returnPlan($plan, $request->user(), (string) $request->input('note'));
        } catch (WorkflowException $e) {
            return back()->withInput()->with('error', $e->getMessage());
        }

        return redirect()->route('plans.show', $plan)->with('success', 'تمت إعادة الخطة للمعلم مع الملاحظات.');
    }

    public function approve(ReviewPlanRequest $request, TherapyPlan $plan): RedirectResponse
    {
        $this->authorize('approve', $plan);

        try {
            $this->workflow->approve($plan, $request->user(), $request->input('note'));
        } catch (WorkflowException $e) {
            return back()->with('error', $e->getMessage());
        }

        return redirect()->route('plans.show', $plan)->with('success', 'تم اعتماد الخطة.');
    }

    private function failure(Request $request, WorkflowException $e): JsonResponse|RedirectResponse
    {
        if ($request->expectsJson()) {
            return response()->json(['message' => $e->getMessage()], 422);
        }

        return back()->withInput()->with('error', $e->getMessage());
    }
}
