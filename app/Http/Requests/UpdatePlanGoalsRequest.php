<?php

namespace App\Http\Requests;

use App\Enums\Semester;
use App\Http\Requests\Concerns\NormalizesInput;
use App\Models\BankGoal;
use App\Models\TherapyPlan;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

/**
 * Saves plan fields (optional) + the full list of goals of a draft/returned plan.
 * Route parameter: {plan} or {therapyPlan}.
 */
class UpdatePlanGoalsRequest extends FormRequest
{
    use NormalizesInput;

    public function authorize(): bool
    {
        $plan = $this->route('plan') ?? $this->route('therapyPlan');

        return $plan instanceof TherapyPlan && $this->user()?->can('update', $plan) === true;
    }

    protected function prepareForValidation(): void
    {
        $this->normalizeDigitsFor(['academic_year', 'start_date_hijri', 'end_date_hijri', 'sessions_count', 'sessions_per_week']);
    }

    public function rules(): array
    {
        return [
            'academic_year' => ['sometimes', 'string', 'max:20', 'regex:/^\d{4}([\/\-]\d{4})?$/'],
            'semester' => ['sometimes', Rule::enum(Semester::class)],
            'start_date_hijri' => ['sometimes', 'string', 'max:20', 'regex:'.self::HIJRI_REGEX],
            'end_date_hijri' => ['sometimes', 'string', 'max:20', 'regex:'.self::HIJRI_REGEX],
            'sessions_count' => ['sometimes', 'integer', 'min:1', 'max:500'],
            'sessions_per_week' => ['sometimes', 'integer', 'min:1', 'max:7'],

            'goals' => ['present', 'array', 'max:200'],
            'goals.*.bank_goal_id' => ['nullable', 'integer', Rule::exists(BankGoal::class, 'id')],
            // Custom (non-bank) goals need their own text and domain.
            'goals.*.goal_text' => ['required_without:goals.*.bank_goal_id', 'nullable', 'string', 'max:5000'],
            'goals.*.domain_name' => ['required_without:goals.*.bank_goal_id', 'nullable', 'string', 'max:255'],
            'goals.*.level' => ['nullable', 'string', 'max:255'],
            'goals.*.subcategory' => ['nullable', 'string', 'max:255'],
            'goals.*.printed_code' => ['nullable', 'string', 'max:50'],
            'goals.*.training_elements' => ['nullable', 'string', 'max:5000'],
            'goals.*.generalization' => ['nullable', 'string', 'max:5000'],
            'goals.*.teacher_note' => ['nullable', 'string', 'max:2000'],
        ];
    }

    public function after(): array
    {
        return [function (Validator $v) {
            $start = $this->hijriToSortable($this->input('start_date_hijri'));
            $end = $this->hijriToSortable($this->input('end_date_hijri'));
            if ($start && $end && $end < $start) {
                $v->errors()->add('end_date_hijri', 'تاريخ نهاية الخطة يجب أن يكون بعد تاريخ البداية.');
            }

            // Same bank goal twice in one plan is almost certainly a mistake.
            $ids = collect($this->input('goals', []))->pluck('bank_goal_id')->filter();
            if ($ids->count() !== $ids->unique()->count()) {
                $v->errors()->add('goals', 'لا يمكن إضافة نفس الهدف أكثر من مرة في الخطة.');
            }
        }];
    }

    /** @return array<string,mixed> plan fields only */
    public function planData(): array
    {
        return $this->safe()->only([
            'academic_year', 'semester', 'start_date_hijri', 'end_date_hijri', 'sessions_count', 'sessions_per_week',
        ]);
    }

    /** @return array<int,array<string,mixed>> */
    public function goalsData(): array
    {
        return array_values($this->validated()['goals'] ?? []);
    }

    public function messages(): array
    {
        return [
            'goals.present' => 'قائمة الأهداف مطلوبة.',
            'goals.array' => 'قائمة الأهداف غير صحيحة.',
            'goals.max' => 'عدد الأهداف كبير جداً.',
            'goals.*.bank_goal_id.exists' => 'أحد الأهداف المختارة غير موجود في بنك الأهداف.',
            'goals.*.goal_text.required_without' => 'نص الهدف مطلوب للأهداف المخصصة.',
            'goals.*.goal_text.max' => 'نص الهدف طويل جداً.',
            'goals.*.domain_name.required_without' => 'مجال الهدف مطلوب للأهداف المخصصة.',
            'goals.*.teacher_note.max' => 'ملاحظة المعلم يجب ألا تتجاوز 2000 حرف.',
            'academic_year.regex' => 'صيغة العام الدراسي غير صحيحة (مثال: 1447-1448).',
            'semester.enum' => 'قيمة الفصل الدراسي غير صحيحة.',
            'start_date_hijri.regex' => 'صيغة تاريخ البداية غير صحيحة (مثال: 1447/03/15).',
            'end_date_hijri.regex' => 'صيغة تاريخ النهاية غير صحيحة (مثال: 1447/06/20).',
            'sessions_count.integer' => 'عدد الجلسات يجب أن يكون رقماً صحيحاً.',
            'sessions_count.min' => 'عدد الجلسات يجب أن يكون 1 على الأقل.',
            'sessions_per_week.integer' => 'عدد الجلسات في الأسبوع يجب أن يكون رقماً صحيحاً.',
            'sessions_per_week.min' => 'عدد الجلسات في الأسبوع يجب أن يكون 1 على الأقل.',
            'sessions_per_week.max' => 'عدد الجلسات في الأسبوع لا يمكن أن يتجاوز 7.',
        ];
    }
}
