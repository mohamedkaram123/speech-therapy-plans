<?php

namespace App\Http\Controllers;

use App\Exceptions\WorkflowException;
use App\Models\TherapyPlan;
use App\Services\PdfService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Response;

class PlanPdfController extends Controller
{
    public function __invoke(TherapyPlan $plan, PdfService $pdf): Response|RedirectResponse
    {
        $this->authorize('download', $plan);

        try {
            return $pdf->planPdfResponse($plan->loadMissing(['goals', 'approver']));
        } catch (WorkflowException $e) {
            return back()->with('error', $e->getMessage());
        }
    }
}
