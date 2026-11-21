<?php

namespace App\Http\Requests;

use App\Models\User;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;

class UpdateTeacherRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->isSupervisor() === true;
    }

    protected function prepareForValidation(): void
    {
        $merge = [
            'email' => strtolower(trim((string) $this->input('email'))),
            'name' => trim((string) $this->input('name')),
        ];
        if ($this->has('is_active')) {
            $merge['is_active'] = $this->boolean('is_active');
        }
        $this->merge($merge);
    }

    public function rules(): array
    {
        $teacher = $this->route('teacher') ?? $this->route('user');
        $id = $teacher instanceof User ? $teacher->id : $teacher;

        return [
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email:rfc', 'max:255', Rule::unique(User::class, 'email')->ignore($id)],
            'password' => ['nullable', 'string', 'confirmed', Password::min(8)],
            'is_active' => ['sometimes', 'boolean'],
        ];
    }

    public function messages(): array
    {
        return [
            'name.required' => 'اسم المعلم مطلوب.',
            'name.max' => 'الاسم يجب ألا يتجاوز 255 حرفاً.',
            'email.required' => 'البريد الإلكتروني مطلوب.',
            'email.email' => 'صيغة البريد الإلكتروني غير صحيحة.',
            'email.unique' => 'البريد الإلكتروني مستخدم مسبقاً.',
            'password.confirmed' => 'تأكيد كلمة المرور غير مطابق.',
            'password.min' => 'كلمة المرور يجب ألا تقل عن 8 أحرف.',
            'is_active.boolean' => 'قيمة حالة الحساب غير صحيحة.',
        ];
    }

    public function attributes(): array
    {
        return ['name' => 'الاسم', 'email' => 'البريد الإلكتروني', 'password' => 'كلمة المرور'];
    }
}
