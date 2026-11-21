<?php

namespace App\Http\Controllers;

use App\Models\BankGoal;
use App\Models\GoalDomain;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class GoalsBankController extends Controller
{
    public function index(Request $request): View
    {
        $this->authorize('viewAny', BankGoal::class);

        $goals = $this->query($request)
            ->with('domain')
            ->orderBy('goal_domain_id')->orderBy('id')
            ->paginate(15)
            ->withQueryString();

        return view('goals-bank.index', [
            'goals' => $goals,
            'domains' => GoalDomain::orderBy('sort_order')->orderBy('id')->get(),
            'facets' => $this->facets($request),
        ]);
    }

    /** JSON feed used by the plan goals editor. */
    public function search(Request $request): JsonResponse
    {
        $this->authorize('viewAny', BankGoal::class);

        $page = $this->query($request)
            ->with('domain')
            ->orderBy('goal_domain_id')->orderBy('id')
            ->paginate(10)
            ->withQueryString();

        return response()->json([
            'data' => $page->getCollection()->map(fn (BankGoal $g) => [
                'id' => $g->id,
                'domain_id' => $g->goal_domain_id,
                'domain_name' => $g->domain?->name,
                'level' => $g->level,
                'subcategory' => $g->subcategory,
                'printed_code' => $g->printed_code,
                'goal_text' => $g->goal_text,
                'training_elements' => $g->training_elements,
                'generalization' => $g->generalization,
            ])->values(),
            'meta' => [
                'current_page' => $page->currentPage(),
                'last_page' => $page->lastPage(),
                'total' => $page->total(),
            ],
            'facets' => $this->facets($request),
        ]);
    }

    private function query(Request $request): Builder
    {
        return BankGoal::query()
            ->when($request->filled('domain_id'), fn ($q) => $q->where('goal_domain_id', $request->integer('domain_id')))
            ->when($request->filled('level'), fn ($q) => $q->where('level', $request->input('level')))
            ->when($request->filled('subcategory'), fn ($q) => $q->where('subcategory', $request->input('subcategory')))
            ->when($request->filled('q'), function ($q) use ($request) {
                $term = '%'.addcslashes(trim((string) $request->input('q')), '%_\\').'%';
                $q->where(fn ($q) => $q->where('goal_text', 'like', $term)
                    ->orWhere('training_elements', 'like', $term)
                    ->orWhere('printed_code', 'like', $term));
            });
    }

    /** Levels / subcategories available for the currently selected domain (and level). */
    private function facets(Request $request): array
    {
        $base = BankGoal::query()
            ->when($request->filled('domain_id'), fn ($q) => $q->where('goal_domain_id', $request->integer('domain_id')));

        $levels = (clone $base)->whereNotNull('level')->where('level', '!=', '')
            ->distinct()->orderBy('level')->pluck('level')->values();

        $subcategories = (clone $base)
            ->when($request->filled('level'), fn ($q) => $q->where('level', $request->input('level')))
            ->whereNotNull('subcategory')->where('subcategory', '!=', '')
            ->distinct()->orderBy('subcategory')->pluck('subcategory')->values();

        return ['levels' => $levels, 'subcategories' => $subcategories];
    }
}
