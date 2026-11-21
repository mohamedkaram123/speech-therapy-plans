<?php

namespace App\Http\Requests;

use App\Enums\GoalResult;
use App\Http\Requests\Concerns\NormalizesInput;
use App\Models\Evaluation;
use App\Models\TherapyPlan;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

/**
 * Teacher saves (action=save) or submits (action=submit) an evaluation.
 * Route parameters: {evaluation} when editing, otherwise {plan}/{therapyPlan} when starting.
 * Draft saves accept partial results; submit requires every goal rated + completion date.
 */
class StoreEvaluationRequest extends FormRequest
{
    use NormalizesInput;

    public function authorize(): bool
    {
        $user = $this->user();
        $evaluation = $this->route('evaluation');
        if ($evaluation instanceof Evaluation) {
            return $user?->can('update', $evaluation) === true;
        }

        $plan = $this->route('plan') ?? $this->route('therapyPlan');

        return $plan instanceof TherapyPlan && $user?->can('create', [Evaluation::class, $plan]) === true;
    }

    protected function prepareForValidation(): void
    {
        $this->normalizeDigitsFor(['evaluation_completed_at_hijri']);
        $this->mergeIfMissing(['action' => 'save']);
    }

    public function isSubmit(): bool
    {
        return $this->input('action') === 'submit';
    }

    public function rules(): array
    {
        $submit = $this->isSubmit();

        return [
            'action' => ['required', 'in:save,submit'],
            'evaluation_completed_at_hijri' => [
                $submit ? 'required' : 'nullable', 'string', 'max:20', 'regex:'.self::HIJRI_REGEX,
            ],
            'results' => [$submit ? 'required' : 'sometimes', 'array'],
            'results.*.therapy_plan_goal_id' => ['required', 'integer', 'exists:therapy_plan_goals,id'],
            'results.*.result' => [$submit ? 'required' : 'nullable', Rule::enum(GoalResult::class)],
            'results.*.note' => ['nullable', 'string', 'max:2000'],
        ];
    }

    public function after(): array
    {
        return [function (Validator $v) {
            $plan = $this->route('evaluation')?->therapyPlan
                ?? $this->route('plan') ?? $this->route('therapyPlan');
            if (! $plan instanceof TherapyPlan) {
                return;
            }

            $planGoalIds = $plan->goals()->pluck('id')->map(fn ($i) => (int) $i)->all();
            $sent = collect($this->input('results', []))->pluck('therapy_plan_goal_id')->map(fn ($i) => (int) $i);

            if ($sent->diff($planGoalIds)->isNotEmpty()) {
                $v->errors()->add('results', 'أحد الأهداف المُقيَّمة لا يتبع هذه الخطة.');
            }
            if ($this->isSubmit() && collect($planGoalIds)->diff($sent)->isNotEmpty()) {
                $v->errors()->add('results', 'يجب تقييم جميع أهداف الخطة قبل الإرسال.');
            }
        }];
    }

    /** @return array<int,array<string,mixed>> */
    public function resultsData(): array
    {
        return array_values($this->validated()['results'] ?? []);
    }

    public function messages(): array
    {
        return [
            'action.in' => 'الإجراء غير صحيح.',
            'evaluation_completed_at_hijri.required' => 'تاريخ إتمام التقييم (هجري) مطلوب قبل الإرسال.',
            'evaluation_completed_at_hijri.regex' => 'صيغة تاريخ إتمام التقييم غير صحيحة (مثال: 1447/06/20).',
            'results.required' => 'نتائج الأهداف مطلوبة قبل الإرسال.',
            'results.array' => 'صيغة النتائج غير صحيحة.',
            'results.*.therapy_plan_goal_id.required' => 'معرّف الهدف مطلوب.',
            'results.*.therapy_plan_goal_id.exists' => 'أحد الأهداف غير موجود.',
            'results.*.result.required' => 'يجب تحديد نتيجة كل هدف (أتقن / في طور التدريب / غير متقن).',
            'results.*.result.enum' => 'قيمة نتيجة الهدف غير صحيحة.',
            'results.*.note.max' => 'ملاحظة الهدف يجب ألا تتجاوز 2000 حرف.',
        ];
    }
}
