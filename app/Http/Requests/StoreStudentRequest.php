<?php

namespace App\Http\Requests;

use App\Enums\Semester;
use App\Enums\StudentPeriod;
use App\Http\Requests\Concerns\NormalizesInput;
use App\Models\User;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreStudentRequest extends FormRequest
{
    use NormalizesInput;

    public function authorize(): bool
    {
        return $this->user()?->isSupervisor() === true;
    }

    protected function prepareForValidation(): void
    {
        $this->normalizeDigitsFor(['birth_date', 'academic_year']);
        $this->merge([
            'full_name' => trim((string) $this->input('full_name')),
            'teacher_id' => $this->filled('teacher_id') ? $this->input('teacher_id') : null,
            'period' => $this->filled('period') ? $this->input('period') : null,
            'semester' => $this->filled('semester') ? $this->input('semester') : null,
            'academic_year' => $this->filled('academic_year') ? trim((string) $this->input('academic_year')) : null,
        ]);
    }

    public function rules(): array
    {
        return [
            'full_name' => ['required', 'string', 'min:3', 'max:255'],
            'birth_date' => ['nullable', 'date', 'before_or_equal:today'],
            'teacher_id' => ['nullable', 'integer', Rule::exists(User::class, 'id')->where(
                fn ($q) => $q->where('role', 'teacher')->where('is_active', true)
            )],
            'period' => ['nullable', Rule::enum(StudentPeriod::class)],
            'academic_year' => ['nullable', 'string', 'max:20'],
            'semester' => ['nullable', Rule::enum(Semester::class)],
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
            'teacher_id.exists' => 'المعلم المختار غير موجود أو حسابه غير مفعّل.',
            'period.enum' => 'قيمة الفترة غير صحيحة.',
            'semester.enum' => 'قيمة الفصل الدراسي غير صحيحة.',
            'diagnosis.max' => 'التشخيص يجب ألا يتجاوز 2000 حرف.',
        ];
    }

    public function attributes(): array
    {
        return [
            'full_name' => 'اسم الطالب',
            'teacher_id' => 'المعلم',
            'period' => 'الفترة',
            'academic_year' => 'العام الدراسي',
            'semester' => 'الفصل الدراسي',
            'diagnosis' => 'التشخيص',
            'birth_date' => 'تاريخ الميلاد',
        ];
    }
}
