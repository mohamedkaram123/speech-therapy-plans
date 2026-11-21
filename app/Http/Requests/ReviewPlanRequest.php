<?php

namespace App\Http\Requests;

use App\Models\TherapyPlan;
use Illuminate\Foundation\Http\FormRequest;

/** Supervisor decision on a pending plan. Route parameter: {plan} or {therapyPlan}. */
class ReviewPlanRequest extends FormRequest
{
    public function authorize(): bool
    {
        $plan = $this->route('plan') ?? $this->route('therapyPlan');

        return $plan instanceof TherapyPlan && $this->user()?->can('review', $plan) === true;
    }

    public function rules(): array
    {
        return [
            'action' => ['required', 'in:approve,return'],
            'note' => ['required_if:action,return', 'nullable', 'string', 'max:2000'],
        ];
    }

    public function messages(): array
    {
        return [
            'action.required' => 'يجب تحديد القرار (اعتماد أو إعادة).',
            'action.in' => 'القرار غير صحيح.',
            'note.required_if' => 'الملاحظة مطلوبة عند إعادة الخطة للمعلم.',
            'note.max' => 'الملاحظة يجب ألا تتجاوز 2000 حرف.',
        ];
    }
}
