<?php

namespace App\Http\Requests;

use App\Models\Evaluation;
use Illuminate\Foundation\Http\FormRequest;

/** Supervisor decision on a pending evaluation (+ report narrative). Route parameter: {evaluation}. */
class ReviewEvaluationRequest extends FormRequest
{
    public function authorize(): bool
    {
        $evaluation = $this->route('evaluation');

        return $evaluation instanceof Evaluation && $this->user()?->can('review', $evaluation) === true;
    }

    public function rules(): array
    {
        $text = ['nullable', 'string', 'max:5000'];

        return [
            'action' => ['required', 'in:approve,return'],
            'note' => ['required_if:action,return', 'nullable', 'string', 'max:2000'],
            'performance_summary' => $text,
            'strengths' => $text,
            'skills_needing_training' => $text,
            'observed_progress' => $text,
            'final_notes' => $text,
            'recommendations' => $text,
        ];
    }

    /** @return array<string,?string> only the report fields that were sent */
    public function reportData(): array
    {
        return $this->safe()->only([
            'performance_summary', 'strengths', 'skills_needing_training',
            'observed_progress', 'final_notes', 'recommendations',
        ]);
    }

    public function messages(): array
    {
        return [
            'action.required' => 'يجب تحديد القرار (اعتماد أو إعادة).',
            'action.in' => 'القرار غير صحيح.',
            'note.required_if' => 'الملاحظة مطلوبة عند إعادة التقييم للمعلم.',
            'note.max' => 'الملاحظة يجب ألا تتجاوز 2000 حرف.',
            'performance_summary.max' => 'ملخص الأداء طويل جداً.',
            'strengths.max' => 'نقاط القوة طويلة جداً.',
            'skills_needing_training.max' => 'حقل المهارات التي تحتاج تدريباً طويل جداً.',
            'observed_progress.max' => 'حقل التقدم الملحوظ طويل جداً.',
            'final_notes.max' => 'الملاحظات الختامية طويلة جداً.',
            'recommendations.max' => 'التوصيات طويلة جداً.',
        ];
    }
}
