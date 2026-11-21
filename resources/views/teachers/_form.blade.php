@php($editing = isset($teacher))
<div class="grid sm:grid-cols-2 gap-4">
    <x-field name="name" label="اسم المعلم" :value="$teacher->name ?? null" required class="sm:col-span-2" />
    <x-field name="email" label="البريد الإلكتروني" type="email" :value="$teacher->email ?? null" required ltr />
    <div></div>
    <x-field name="password" label="كلمة المرور" type="password" :required="! $editing" ltr autocomplete="new-password"
             :hint="$editing ? 'اتركها فارغة للإبقاء على كلمة المرور الحالية.' : 'ثمانية أحرف على الأقل.'" />
    <x-field name="password_confirmation" label="تأكيد كلمة المرور" type="password" :required="! $editing" ltr autocomplete="new-password" />
</div>
<label class="mt-4 inline-flex items-center gap-2">
    <input type="hidden" name="is_active" value="0">
    <input type="checkbox" name="is_active" value="1" class="rounded border-slate-300 text-teal-700 focus:ring-teal-600"
           @checked(old('is_active', $teacher->is_active ?? true))>
    <span class="text-sm">الحساب نشط</span>
</label>
<div class="mt-6 flex gap-2">
    <button class="btn-primary">حفظ</button>
    <a href="{{ route('teachers.index') }}" class="btn-secondary">إلغاء</a>
</div>
