<div class="grid sm:grid-cols-2 gap-4">
    <x-field name="full_name" label="اسم الطالب" :value="$student->full_name" required class="sm:col-span-2" />

    <x-select-field
        name="teacher_id"
        label="المعلم المسؤول"
        :options="$teachers->pluck('name', 'id')"
        :value="$student->teacher_id"
        placeholder="— يُحدد لاحقاً —"
    />

    <x-select-field
        name="period"
        label="الفترة"
        :options="collect(\App\Enums\StudentPeriod::cases())->mapWithKeys(fn ($p) => [$p->value => $p->label()])"
        :value="$student->period?->value"
        placeholder="— يُحدد لاحقاً —"
    />

    <x-field
        name="academic_year"
        label="العام الدراسي"
        :value="$student->academic_year"
        placeholder="مثال: 1448-1449"
        hint="اختياري — يمكن إضافته لاحقاً"
    />

    <x-select-field
        name="semester"
        label="الفصل الدراسي"
        :options="collect(\App\Enums\Semester::cases())->mapWithKeys(fn ($s) => [$s->value => $s->label()])"
        :value="$student->semester?->value"
        placeholder="— يُحدد لاحقاً —"
    />

    <div class="sm:col-span-2">
        <label for="birth_date" class="label">تاريخ الميلاد <span class="badge bg-slate-200 text-slate-700 ms-1">ميلادي</span></label>
        <input type="date" id="birth_date" name="birth_date" class="input" dir="ltr" value="{{ old('birth_date', $student->birth_date?->format('Y-m-d')) }}">
        <p class="hint">اختياري — بالتاريخ الميلادي فقط.</p>
        <x-input-error :messages="$errors->get('birth_date')" class="mt-1" />
    </div>

    <div class="sm:col-span-2">
        <label for="diagnosis" class="label">التشخيص</label>
        <textarea id="diagnosis" name="diagnosis" rows="3" class="input">{{ old('diagnosis', $student->diagnosis) }}</textarea>
        <x-input-error :messages="$errors->get('diagnosis')" class="mt-1" />
    </div>
</div>
<div class="mt-6 flex gap-2">
    <button class="btn-primary">حفظ</button>
    <a href="{{ route('students.index') }}" class="btn-secondary">إلغاء</a>
</div>
