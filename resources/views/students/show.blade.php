<x-app-layout :title="$student->full_name">
    <x-slot name="header">
        <h1 class="text-xl font-bold text-slate-800">{{ $student->full_name }}</h1>
        <div class="flex gap-2">
            @can('create', \App\Models\TherapyPlan::class)
                <a href="{{ route('plans.create', $student) }}" class="btn-primary btn-sm">+ خطة جديدة</a>
            @endcan
            @can('update', $student)
                <a href="{{ route('students.edit', $student) }}" class="btn-secondary btn-sm">تعديل</a>
            @endcan
        </div>
    </x-slot>

    <div class="grid lg:grid-cols-3 gap-6">
        <section class="card card-body lg:col-span-1">
            <h2 class="card-title mb-3">بيانات الطالب</h2>
            <dl class="text-sm space-y-2">
                <div class="flex justify-between gap-3"><dt class="text-slate-500">المعلم</dt><dd class="font-semibold">{{ $student->teacher?->name ?: '—' }}</dd></div>
                <div class="flex justify-between gap-3"><dt class="text-slate-500">الفترة</dt><dd>{{ $student->period?->label() ?: '—' }}</dd></div>
                <div class="flex justify-between gap-3"><dt class="text-slate-500">العام الدراسي</dt><dd>{{ $student->academic_year ?: '—' }}</dd></div>
                <div class="flex justify-between gap-3"><dt class="text-slate-500">الفصل الدراسي</dt><dd>{{ $student->semester?->label() ?: '—' }}</dd></div>
                <div class="flex justify-between gap-3"><dt class="text-slate-500">الميلاد (ميلادي)</dt><dd dir="ltr">{{ $student->birth_date?->format('Y-m-d') ?: '—' }}</dd></div>
                <div><dt class="text-slate-500 mb-1">التشخيص</dt><dd>{{ $student->diagnosis ?: '—' }}</dd></div>
            </dl>
        </section>

        <section class="card lg:col-span-2">
            <div class="card-body pb-2"><h2 class="card-title">الخطط الفردية</h2></div>
            @forelse ($student->therapyPlans as $plan)
                <div class="flex flex-wrap items-center justify-between gap-3 px-4 sm:px-6 py-3 border-t border-slate-100">
                    <div>
                        <a href="{{ route('plans.show', $plan) }}" class="font-semibold hover:text-teal-700">{{ $plan->semester->label() }} {{ $plan->academic_year }}</a>
                        <div class="mt-1"><x-status-badge :status="$plan->status" />
                            @if($plan->evaluation)<span class="text-xs text-slate-500 ms-2">التقييم: {{ $plan->evaluation->status->label() }}</span>@endif
                        </div>
                    </div>
                    <a href="{{ route('plans.show', $plan) }}" class="btn-secondary btn-sm">عرض</a>
                </div>
            @empty
                <x-empty-state>لا توجد خطط لهذا الطالب بعد.</x-empty-state>
            @endforelse
        </section>
    </div>
</x-app-layout>
