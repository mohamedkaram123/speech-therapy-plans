<x-app-layout title="خطة جديدة">
    <x-slot name="header">
        <h1 class="text-xl font-bold text-slate-800">إنشاء خطة فردية</h1>
        <a href="{{ route('students.show', $student) }}" class="btn-secondary btn-sm">رجوع للطالب</a>
    </x-slot>

    <div class="card card-body max-w-3xl mb-4 text-sm grid sm:grid-cols-3 gap-3">
        <div><span class="text-slate-500">الطالب:</span> <b>{{ $student->full_name }}</b></div>
        <div><span class="text-slate-500">الفترة:</span> {{ $student->period->label() }}</div>
        <div><span class="text-slate-500">المعلم:</span> {{ $student->teacher?->name }}</div>
    </div>

    <form method="POST" action="{{ route('plans.store') }}" class="card card-body max-w-3xl">
        @csrf
        <input type="hidden" name="student_id" value="{{ $student->id }}">
        <div class="grid sm:grid-cols-2 gap-4">
            <x-field name="academic_year" label="العام الدراسي" :value="$defaults['academic_year']" required ltr hint="مثال: 1448-1449 (هجري)" />
            <x-select-field name="semester" label="الفصل الدراسي"
                :options="collect($semesters)->mapWithKeys(fn ($s) => [$s->value => $s->label()])" :value="$defaults['semester']" required />
            <x-hijri-input name="start_date_hijri" label="تاريخ بداية الخطة" :value="$defaults['start_date_hijri']" required />
            <x-hijri-input name="end_date_hijri" label="تاريخ نهاية الخطة" required example="1448/06/20" />
            <x-field name="sessions_count" label="عدد الجلسات" type="number" min="1" max="500" required ltr />
            <x-field name="sessions_per_week" label="عدد الجلسات في الأسبوع" type="number" min="1" max="7" required ltr />
        </div>
        <div class="mt-6 flex gap-2">
            <button class="btn-primary">إنشاء الخطة وإضافة الأهداف</button>
            <a href="{{ route('students.index') }}" class="btn-secondary">إلغاء</a>
        </div>
    </form>
</x-app-layout>
