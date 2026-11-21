<?php

namespace App\Http\Requests;

use App\Enums\StudentPeriod;
use App\Http\Requests\Concerns\NormalizesInput;
use App\Models\User;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateStudentRequest extends FormRequest
{
    use NormalizesInput;

    public function authorize(): bool
    {
        return $this->user()?->isSupervisor() === true;
    }

    protected function prepareForValidation(): void
    {
        $this->normalizeDigitsFor(['birth_date', 'birth_date_hijri']);
        $this->merge(['full_name' => trim((string) $this->input('full_name'))]);
    }

    public function rules(): array
    {
        return [
            'full_name' => ['required', 'string', 'min:3', 'max:255'],
            'birth_date' => ['nullable', 'date', 'before_or_equal:today'],
            'birth_date_hijri' => ['nullable', 'string', 'max:20', 'regex:'.self::HIJRI_REGEX],
            // Only active teachers can have students assigned.
            'teacher_id' => ['required', 'integer', Rule::exists(User::class, 'id')->where(
                fn ($q) => $q->where('role', 'teacher')->where('is_active', true)
            )],
            'period' => ['required', Rule::enum(StudentPeriod::class)],
            'diagnosis' => ['nullable', 'string', 'max:2000'],
        ];
    }

    public function messages(): array
    {
        return [
            'full_name.required' => 'اسم الطالب مطلوب.',
            'full_name.min' => 'اسم الطالب قصير جداً.',
            'full_name.max' => 'اسم الطالب يجب ألا يتجاوز 255 حرفاً.',
            'birth_date.date' => 'تاريخ الميلاد الميلادي غير صحيح.',
            'birth_date.before_or_equal' => 'تاريخ الميلاد لا يمكن أن يكون في المستقبل.',
            'birth_date_hijri.regex' => 'صيغة تاريخ الميلاد الهجري غير صحيحة (مثال: 1440/05/12).',
            'birth_date_hijri.max' => 'تاريخ الميلاد الهجري طويل جداً.',
            'teacher_id.required' => 'يجب تحديد المعلم المسؤول عن الطالب.',
            'teacher_id.exists' => 'المعلم المختار غير موجود أو حسابه غير مفعّل.',
            'period.required' => 'فترة الطالب (صباحي/مسائي) مطلوبة.',
            'period.enum' => 'قيمة الفترة غير صحيحة.',
            'diagnosis.max' => 'التشخيص يجب ألا يتجاوز 2000 حرف.',
        ];
    }

    public function attributes(): array
    {
        return ['full_name' => 'اسم الطالب', 'teacher_id' => 'المعلم', 'period' => 'الفترة', 'diagnosis' => 'التشخيص'];
    }
}
