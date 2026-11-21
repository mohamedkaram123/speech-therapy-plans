<?php

namespace App\Http\Requests;

use App\Models\User;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Password;

class StoreTeacherRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->isSupervisor() === true;
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'email' => strtolower(trim((string) $this->input('email'))),
            'name' => trim((string) $this->input('name')),
            'is_active' => $this->has('is_active') ? $this->boolean('is_active') : true,
        ]);
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email:rfc', 'max:255', 'unique:'.User::class.',email'],
            'password' => ['required', 'string', 'confirmed', Password::min(8)],
            'is_active' => ['boolean'],
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
            'password.required' => 'كلمة المرور مطلوبة.',
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
