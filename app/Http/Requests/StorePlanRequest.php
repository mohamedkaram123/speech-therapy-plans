<?php

namespace App\Http\Requests;

use App\Enums\Semester;
use App\Enums\StudentPeriod;
use App\Http\Requests\Concerns\NormalizesInput;
use App\Models\Student;
use App\Models\TherapyPlan;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class StorePlanRequest extends FormRequest
{
    use NormalizesInput;

    public function authorize(): bool
    {
        $user = $this->user();
        if (! $user || ! $user->can('create', TherapyPlan::class)) {
            return false;
        }

        // A teacher may only create plans for students assigned to them.
        $student = Student::find($this->input('student_id'));

        return $student === null || $user->can('view', $student);
    }

    protected function prepareForValidation(): void
    {
        $this->normalizeDigitsFor(['academic_year', 'start_date_hijri', 'end_date_hijri', 'sessions_count', 'sessions_per_week']);
    }

    public function rules(): array
    {
        return [
            'student_id' => ['required', 'integer', Rule::exists(Student::class, 'id')->whereNull('deleted_at')],
            'academic_year' => ['required', 'string', 'max:20', 'regex:/^\d{4}([\/\-]\d{4})?$/'],
            'semester' => ['required', Rule::enum(Semester::class)],
            'start_date_hijri' => ['required', 'string', 'max:20', 'regex:'.self::HIJRI_REGEX],
            'end_date_hijri' => ['required', 'string', 'max:20', 'regex:'.self::HIJRI_REGEX],
            'sessions_count' => ['required', 'integer', 'min:1', 'max:500'],
            'sessions_per_week' => ['required', 'integer', 'min:1', 'max:7'],
            'period' => ['nullable', Rule::enum(StudentPeriod::class)],
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

            $student = Student::find($this->input('student_id'));
            if ($student && ! $student->period && ! $this->filled('period')) {
                $v->errors()->add('period', 'فترة الطالب (صباحي/مسائي) مطلوبة.');
            }

            // One plan per student / year / semester.
            if (! $v->errors()->hasAny(['student_id', 'academic_year', 'semester'])) {
                $exists = TherapyPlan::query()
                    ->where('student_id', $this->input('student_id'))
                    ->where('academic_year', $this->input('academic_year'))
                    ->where('semester', $this->input('semester'))
                    ->exists();
                if ($exists) {
                    $v->errors()->add('semester', 'توجد خطة لهذا الطالب في نفس العام والفصل الدراسي.');
                }
            }
        }];
    }

    public function messages(): array
    {
        return [
            'student_id.required' => 'يجب اختيار الطالب.',
            'student_id.exists' => 'الطالب المختار غير موجود.',
            'academic_year.required' => 'العام الدراسي مطلوب.',
            'academic_year.regex' => 'صيغة العام الدراسي غير صحيحة (مثال: 1447-1448).',
            'semester.required' => 'الفصل الدراسي مطلوب.',
            'semester.enum' => 'قيمة الفصل الدراسي غير صحيحة.',
            'start_date_hijri.required' => 'تاريخ بداية الخطة (هجري) مطلوب.',
            'start_date_hijri.regex' => 'صيغة تاريخ البداية غير صحيحة (مثال: 1447/03/15).',
            'end_date_hijri.required' => 'تاريخ نهاية الخطة (هجري) مطلوب.',
            'end_date_hijri.regex' => 'صيغة تاريخ النهاية غير صحيحة (مثال: 1447/06/20).',
            'sessions_count.required' => 'عدد الجلسات مطلوب.',
            'sessions_count.integer' => 'عدد الجلسات يجب أن يكون رقماً صحيحاً.',
            'sessions_count.min' => 'عدد الجلسات يجب أن يكون 1 على الأقل.',
            'sessions_count.max' => 'عدد الجلسات كبير جداً.',
            'sessions_per_week.required' => 'عدد الجلسات في الأسبوع مطلوب.',
            'sessions_per_week.integer' => 'عدد الجلسات في الأسبوع يجب أن يكون رقماً صحيحاً.',
            'sessions_per_week.min' => 'عدد الجلسات في الأسبوع يجب أن يكون 1 على الأقل.',
            'sessions_per_week.max' => 'عدد الجلسات في الأسبوع لا يمكن أن يتجاوز 7.',
        ];
    }
}
