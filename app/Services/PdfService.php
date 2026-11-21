<?php

namespace App\Services;

use App\Enums\EvaluationStatus;
use App\Enums\PlanStatus;
use App\Exceptions\WorkflowException;
use App\Models\Evaluation;
use App\Models\TherapyPlan;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\View;
use Mpdf\Config\ConfigVariables;
use Mpdf\Config\FontVariables;
use Mpdf\Mpdf;
use Symfony\Component\HttpFoundation\HeaderUtils;

class PdfService
{
    private const FONT_KEY = 'notonaskh';

    public function __construct(private readonly PlanWorkflowService $plans) {}

    // ------------------------------------------------------------------
    // Plan
    // ------------------------------------------------------------------

    /** Binary PDF of an approved plan (no evaluation results). */
    public function planPdf(TherapyPlan $plan): string
    {
        if ($plan->status !== PlanStatus::Approved) {
            throw new WorkflowException('لا يمكن طباعة الخطة إلا بعد اعتمادها.');
        }

        // Frozen snapshot when present, otherwise live (approved) data.
        $data = $plan->locked_snapshot ?: $this->plans->buildSnapshot($plan->loadMissing(['goals', 'approver']));

        $html = View::make('pdf.plan', [
            'data' => $data,
            'center' => $this->center(),
        ])->render();

        return $this->render($html, 'الخطة الفردية - '.($data['student']['full_name'] ?? ''));
    }

    public function planPdfResponse(TherapyPlan $plan, bool $inline = false): Response
    {
        $name = 'خطة-'.($plan->student_name ?? $plan->id).'.pdf';

        return $this->toResponse($this->planPdf($plan), $name, $inline);
    }

    // ------------------------------------------------------------------
    // Evaluation report
    // ------------------------------------------------------------------

    /** Binary PDF of an approved evaluation, from its locked snapshot. */
    public function evaluationReportPdf(Evaluation $evaluation): string
    {
        if ($evaluation->status !== EvaluationStatus::Approved) {
            throw new WorkflowException('لا يمكن طباعة التقرير إلا بعد اعتماد التقييم.');
        }
        if (empty($evaluation->locked_snapshot)) {
            throw new WorkflowException('لا توجد نسخة معتمدة مجمّدة لهذا التقييم.');
        }

        $data = $evaluation->locked_snapshot;

        $html = View::make('pdf.evaluation', [
            'data' => $data,
            'center' => $this->center(),
        ])->render();

        return $this->render($html, 'تقرير التقييم - '.($data['plan']['student']['full_name'] ?? ''));
    }

    public function evaluationReportPdfResponse(Evaluation $evaluation, bool $inline = false): Response
    {
        $evaluation->loadMissing('therapyPlan');
        $name = 'تقرير-'.($evaluation->therapyPlan->student_name ?? $evaluation->id).'.pdf';

        return $this->toResponse($this->evaluationReportPdf($evaluation), $name, $inline);
    }

    // ------------------------------------------------------------------
    // Internals
    // ------------------------------------------------------------------

    /** @return array{name:string,dept:string} */
    private function center(): array
    {
        return [
            'name' => config('speech.center_name', 'مركز السنابل المكية'),
            'dept' => config('speech.center_dept', 'قسم التربية الخاصة'),
        ];
    }

    private function render(string $html, string $title): string
    {
        $mpdf = $this->makeMpdf();
        $mpdf->SetTitle($title);
        $mpdf->WriteHTML($html);

        return $mpdf->Output('', 'S');
    }

    private function makeMpdf(): Mpdf
    {
        [$regular, $bold, $useOtl] = $this->resolveFonts();

        $tmp = storage_path('app/mpdf');
        File::ensureDirectoryExists($tmp);

        $defaults = (new ConfigVariables)->getDefaults();
        $fontDefaults = (new FontVariables)->getDefaults();

        $fontData = [
            'R' => basename($regular),
            'B' => basename($bold),
            // OpenType-layout shaping gives correct Arabic joining/marks. It needs the
            // MarkFilteringSet-free copy of the font (see resolveFonts()); otherwise we
            // fall back to mPDF's built-in (lower quality) shaping.
            'useOTL' => $useOtl ? 0xFF : 0,
        ];

        return new Mpdf([
            'mode' => 'utf-8',
            'format' => 'A4',
            'orientation' => 'P',
            'directionality' => 'rtl',
            'tempDir' => $tmp,
            'fontDir' => array_values(array_unique(array_merge(
                $defaults['fontDir'],
                [dirname($regular), dirname($bold)],
            ))),
            'fontdata' => $fontDefaults['fontdata'] + [self::FONT_KEY => $fontData],
            'default_font' => self::FONT_KEY,
            'default_font_size' => 11,
            // Glyphs missing from the Arabic font fall back to DejaVu Sans.
            'useSubstitutions' => true,
            'backupSubsFont' => ['dejavusans'],
            'autoScriptToLang' => true,
            'autoLangToFont' => false,
            'margin_left' => 12,
            'margin_right' => 12,
            'margin_top' => 12,
            'margin_bottom' => 14,
        ]);
    }

    /**
     * mPDF's OTL engine throws "contains MarkGlyphSets" for the stock Noto Naskh
     * Arabic. resources/fonts holds copies with that GDEF lookup flag stripped
     * (scripts/patch_mpdf_font.py); when a copy with the same file name as the
     * configured font exists we use it with OTL, otherwise the configured font
     * is used as-is without OTL.
     *
     * @return array{0:string,1:string,2:bool} regular path, bold path, use OTL
     */
    private function resolveFonts(): array
    {
        $regular = config('speech.pdf_font')
            ?? config('speech.pdf.arabic_font_path')
            ?? '/usr/share/fonts/truetype/noto/NotoNaskhArabic-Regular.ttf';

        if (! is_file($regular)) {
            throw new WorkflowException("خط PDF العربي غير موجود: {$regular}");
        }

        $bold = config('speech.pdf.arabic_font_bold_path') ?? str_replace('-Regular', '-Bold', $regular);
        $bold = is_file($bold) ? $bold : $regular;

        $patchedRegular = resource_path('fonts/'.basename($regular));
        $patchedBold = resource_path('fonts/'.basename($bold));

        if (is_file($patchedRegular)) {
            return [$patchedRegular, is_file($patchedBold) ? $patchedBold : $patchedRegular, true];
        }

        return [$regular, $bold, false];
    }

    private function toResponse(string $binary, string $filename, bool $inline): Response
    {
        $fallback = 'document-'.md5($filename).'.pdf'; // ASCII fallback for old clients

        return new Response($binary, 200, [
            'Content-Type' => 'application/pdf',
            'Content-Length' => (string) strlen($binary),
            'Content-Disposition' => HeaderUtils::makeDisposition(
                $inline ? HeaderUtils::DISPOSITION_INLINE : HeaderUtils::DISPOSITION_ATTACHMENT,
                $filename,
                $fallback,
            ),
        ]);
    }
}
