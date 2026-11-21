<?php

namespace App\Http\Controllers;

use App\Exceptions\WorkflowException;
use App\Models\Evaluation;
use App\Services\PdfService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Response;

class EvaluationPdfController extends Controller
{
    public function __invoke(Evaluation $evaluation, PdfService $pdf): Response|RedirectResponse
    {
        $this->authorize('download', $evaluation);

        try {
            return $pdf->evaluationReportPdfResponse($evaluation);
        } catch (WorkflowException $e) {
            return back()->with('error', $e->getMessage());
        }
    }
}
