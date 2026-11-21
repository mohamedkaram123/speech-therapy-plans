<x-app-layout title="لوحة التحكم">
    <x-slot name="header">
        <div>
            <div class="flex items-center gap-2 mb-1">
                <span class="inline-flex items-center rounded-md bg-amber-100 text-amber-900 border border-amber-300 px-2 py-0.5 text-[11px] font-bold">وضع المشرف</span>
            </div>
            <h1 class="text-xl font-bold text-slate-900">لوحة المشرف</h1>
        </div>
        <div class="flex gap-2">
            <a href="{{ route('students.create') }}" class="btn-secondary btn-sm">+ طالب</a>
            <a href="{{ route('teachers.create') }}" class="btn-secondary btn-sm">+ معلم</a>
            <a href="{{ route('tracking.index') }}" class="btn-primary btn-sm">كشف الاستلام</a>
        </div>
    </x-slot>

    <div class="grid grid-cols-2 lg:grid-cols-3 xl:grid-cols-6 gap-3 mb-6">
        <x-stat-card label="المعلمون النشطون" :value="$stats['teachers']" :href="route('teachers.index')" />
        <x-stat-card label="الطلاب" :value="$stats['students']" tone="slate" :href="route('students.index')" />
        <x-stat-card label="خطط بانتظار المراجعة" :value="$stats['pending_plans']" tone="amber" :href="route('plans.index', ['status' => 'pending_review'])" />
        <x-stat-card label="تقييمات بانتظار المراجعة" :value="$stats['pending_evaluations']" tone="amber" :href="route('evaluations.index', ['status' => 'pending_review'])" />
        <x-stat-card label="خطط معتمدة" :value="$stats['approved_plans']" tone="emerald" :href="route('plans.index', ['status' => 'approved'])" />
        <x-stat-card label="خطط معادة للتعديل" :value="$stats['returned_plans']" tone="rose" :href="route('plans.index', ['status' => 'returned'])" />
    </div>

    <div class="grid lg:grid-cols-2 gap-6">
        <section class="card">
            <div class="card-body pb-2 flex items-center justify-between">
                <h2 class="card-title">خطط بانتظار المراجعة</h2>
                <a href="{{ route('plans.index', ['status' => 'pending_review']) }}" class="text-sm text-teal-700 hover:underline">عرض الكل</a>
            </div>
            @forelse ($pendingPlans as $plan)
                <a href="{{ route('plans.show', $plan) }}" class="flex items-center justify-between gap-3 px-4 sm:px-6 py-3 border-t border-slate-100 hover:bg-slate-50">
                    <div>
                        <div class="font-semibold text-slate-800">{{ $plan->student_name }}</div>
                        <div class="text-xs text-slate-500">المعلم: {{ $plan->teacher_name }} &middot; {{ $plan->semester->label() }} {{ $plan->academic_year }}</div>
                    </div>
                    <div class="text-xs text-slate-500 text-end"><x-date-cell :date="$plan->submitted_at" /></div>
                </a>
            @empty
                <x-empty-state>لا توجد خطط بانتظار المراجعة.</x-empty-state>
            @endforelse
        </section>

        <section class="card">
            <div class="card-body pb-2 flex items-center justify-between">
                <h2 class="card-title">تقييمات بانتظار المراجعة</h2>
                <a href="{{ route('evaluations.index', ['status' => 'pending_review']) }}" class="text-sm text-teal-700 hover:underline">عرض الكل</a>
            </div>
            @forelse ($pendingEvaluations as $evaluation)
                <a href="{{ route('evaluations.show', $evaluation) }}" class="flex items-center justify-between gap-3 px-4 sm:px-6 py-3 border-t border-slate-100 hover:bg-slate-50">
                    <div>
                        <div class="font-semibold text-slate-800">{{ $evaluation->therapyPlan->student_name }}</div>
                        <div class="text-xs text-slate-500">المعلم: {{ $evaluation->therapyPlan->teacher_name }} &middot; {{ $evaluation->therapyPlan->academic_year }}</div>
                    </div>
                    <div class="text-xs text-slate-500 text-end"><x-date-cell :date="$evaluation->submitted_at" /></div>
                </a>
            @empty
                <x-empty-state>لا توجد تقييمات بانتظار المراجعة.</x-empty-state>
            @endforelse
        </section>
    </div>
</x-app-layout>
