<?php

namespace App\Http\Controllers;

use App\Enums\EvaluationStatus;
use App\Enums\GoalResult;
use App\Enums\ReviewAction;
use App\Exceptions\WorkflowException;
use App\Http\Requests\ReviewEvaluationRequest;
use App\Http\Requests\StoreEvaluationRequest;
use App\Models\Evaluation;
use App\Models\TherapyPlan;
use App\Services\EvaluationWorkflowService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class EvaluationController extends Controller
{
    public function __construct(private readonly EvaluationWorkflowService $workflow) {}

    public function index(Request $request): View
    {
        $this->authorize('viewAny', Evaluation::class);
        $user = $request->user();

        $evaluations = Evaluation::query()
            ->with('therapyPlan')
            ->when($user->isTeacher(), fn ($q) => $q->whereHas('therapyPlan', fn ($q) => $q->where('teacher_id', $user->id)))
            ->when($request->filled('status') && EvaluationStatus::tryFrom((string) $request->input('status')),
                fn ($q) => $q->where('status', $request->input('status')))
            ->latest('updated_at')
            ->paginate(20)
            ->withQueryString();

        // Teacher: approved plans that still wait for an evaluation.
        $awaiting = $user->isTeacher()
            ? TherapyPlan::where('teacher_id', $user->id)->where('status', \App\Enums\PlanStatus::Approved)
                ->whereDoesntHave('evaluation')->orderBy('student_name')->get()
            : collect();

        return view('evaluations.index', compact('evaluations', 'awaiting'));
    }

    /** Starts (or resumes) the evaluation of an approved plan. */
    public function start(Request $request, TherapyPlan $plan): RedirectResponse
    {
        $this->authorize('view', $plan);

        if ($plan->evaluation()->exists()) {
            $existing = $plan->evaluation;

            return redirect()->route(
                $request->user()->can('update', $existing) ? 'evaluations.edit' : 'evaluations.show',
                $existing,
            );
        }

        $this->authorize('create', [Evaluation::class, $plan]);

        try {
            $evaluation = $this->workflow->start($plan, $request->user());
        } catch (WorkflowException $e) {
            return back()->with('error', $e->getMessage());
        }

        return redirect()->route('evaluations.edit', $evaluation)->with('success', 'تم بدء التقييم. قيّم كل هدف ثم أرسل التقييم للمراجعة.');
    }

    public function show(Evaluation $evaluation): View
    {
        $this->authorize('view', $evaluation);

        $evaluation->load(['therapyPlan.student', 'therapyPlan.goals', 'goalResults', 'reviews.user', 'approver']);

        return view('evaluations.show', [
            'evaluation' => $evaluation,
            'plan' => $evaluation->therapyPlan,
            'results' => $evaluation->goalResults->keyBy('therapy_plan_goal_id'),
            'reportFields' => $this->reportLabels(),
            'lastReturn' => $evaluation->reviews->where('action', ReviewAction::Returned)->last(),
        ]);
    }

    public function edit(Evaluation $evaluation): View
    {
        $this->authorize('update', $evaluation);

        $evaluation->load(['therapyPlan.student', 'therapyPlan.goals', 'goalResults', 'reviews.user']);

        return view('evaluations.edit', [
            'evaluation' => $evaluation,
            'plan' => $evaluation->therapyPlan,
            'results' => $evaluation->goalResults->keyBy('therapy_plan_goal_id'),
            'options' => GoalResult::cases(),
            'lastReturn' => $evaluation->reviews->where('action', ReviewAction::Returned)->last(),
        ]);
    }

    /** Saves the draft; with action=submit also sends it to the supervisor. */
    public function update(StoreEvaluationRequest $request, Evaluation $evaluation): RedirectResponse
    {
        $this->authorize('update', $evaluation);

        try {
            $this->workflow->saveDraft($evaluation, $request->safe()->only('evaluation_completed_at_hijri'), $request->resultsData());

            if ($request->isSubmit()) {
                $this->authorize('submit', $evaluation);
                $this->workflow->submit($evaluation, $request->user());

                return redirect()->route('evaluations.show', $evaluation)->with('success', 'تم إرسال التقييم للمراجعة.');
            }
        } catch (WorkflowException $e) {
            return back()->withInput()->with('error', $e->getMessage());
        }

        return back()->with('success', 'تم حفظ مسودة التقييم.');
    }

    public function submit(Request $request, Evaluation $evaluation): RedirectResponse
    {
        $this->authorize('submit', $evaluation);

        try {
            $this->workflow->submit($evaluation, $request->user());
        } catch (WorkflowException $e) {
            return back()->with('error', $e->getMessage());
        }

        return redirect()->route('evaluations.show', $evaluation)->with('success', 'تم إرسال التقييم للمراجعة.');
    }

    public function returnEvaluation(ReviewEvaluationRequest $request, Evaluation $evaluation): RedirectResponse
    {
        $this->authorize('return', $evaluation);

        try {
            $this->workflow->returnEvaluation($evaluation, $request->user(), (string) $request->input('note'), $request->reportData());
        } catch (WorkflowException $e) {
            return back()->withInput()->with('error', $e->getMessage());
        }

        return redirect()->route('evaluations.show', $evaluation)->with('success', 'تمت إعادة التقييم للمعلم مع الملاحظات.');
    }

    public function approve(ReviewEvaluationRequest $request, Evaluation $evaluation): RedirectResponse
    {
        $this->authorize('approve', $evaluation);

        try {
            $this->workflow->approveEvaluation($evaluation, $request->user(), $request->input('note'), $request->reportData());
        } catch (WorkflowException $e) {
            return back()->withInput()->with('error', $e->getMessage());
        }

        return redirect()->route('evaluations.show', $evaluation)->with('success', 'تم اعتماد التقييم.');
    }

    /** @return array<string,string> */
    private function reportLabels(): array
    {
        return [
            'performance_summary' => 'ملخص الأداء',
            'strengths' => 'نقاط القوة',
            'skills_needing_training' => 'المهارات التي تحتاج إلى تدريب',
            'observed_progress' => 'التقدم الملحوظ',
            'final_notes' => 'ملاحظات ختامية',
            'recommendations' => 'التوصيات',
        ];
    }
}
