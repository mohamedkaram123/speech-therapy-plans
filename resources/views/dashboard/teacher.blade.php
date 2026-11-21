<x-app-layout title="لوحة التحكم">
    <x-slot name="header">
        <h1 class="text-xl font-bold text-slate-800">مرحباً {{ auth()->user()->name }}</h1>
        <a href="{{ route('students.index') }}" class="btn-primary btn-sm">طلابي ({{ $studentsCount }})</a>
    </x-slot>

    @if ($returnedPlans->isNotEmpty())
        <section class="card border-rose-200 mb-6">
            <div class="card-body pb-2"><h2 class="card-title text-rose-700">خطط معادة للتعديل</h2></div>
            @foreach ($returnedPlans as $plan)
                @php($note = $plan->reviews->where('action', \App\Enums\ReviewAction::Returned)->last()?->note)
                <div class="flex flex-wrap items-center justify-between gap-3 px-4 sm:px-6 py-3 border-t border-slate-100">
                    <div>
                        <div class="font-semibold">{{ $plan->student_name }}</div>
                        @if($note)<div class="text-sm text-rose-700 mt-0.5">ملاحظة المشرف: {{ $note }}</div>@endif
                    </div>
                    <a href="{{ route('plans.edit', $plan) }}" class="btn-primary btn-sm">تعديل الخطة</a>
                </div>
            @endforeach
        </section>
    @endif

    <div class="grid lg:grid-cols-2 gap-6">
        <section class="card">
            <div class="card-body pb-2"><h2 class="card-title">طلاب بلا خطة</h2></div>
            @forelse ($studentsWithoutPlan as $student)
                <div class="flex items-center justify-between gap-3 px-4 sm:px-6 py-3 border-t border-slate-100">
                    <div>
                        <div class="font-semibold">{{ $student->full_name }}</div>
                        <div class="text-xs text-slate-500">{{ $student->period?->label() ?: '—' }}</div>
                    </div>
                    <a href="{{ route('plans.create', $student) }}" class="btn-primary btn-sm">إنشاء خطة</a>
                </div>
            @empty
                <x-empty-state>جميع طلابك لديهم خطط.</x-empty-state>
            @endforelse
        </section>

        <section class="card">
            <div class="card-body pb-2"><h2 class="card-title">مسودات قيد الإعداد</h2></div>
            @forelse ($draftPlans as $plan)
                <div class="flex items-center justify-between gap-3 px-4 sm:px-6 py-3 border-t border-slate-100">
                    <div>
                        <div class="font-semibold">{{ $plan->student_name }}</div>
                        <div class="text-xs text-slate-500">{{ $plan->semester->label() }} {{ $plan->academic_year }}</div>
                    </div>
                    <a href="{{ route('plans.edit', $plan) }}" class="btn-secondary btn-sm">متابعة التحرير</a>
                </div>
            @empty
                <x-empty-state>لا توجد مسودات.</x-empty-state>
            @endforelse
        </section>

        <section class="card">
            <div class="card-body pb-2"><h2 class="card-title">خطط بانتظار المراجعة</h2></div>
            @forelse ($pendingPlans as $plan)
                <a href="{{ route('plans.show', $plan) }}" class="flex items-center justify-between gap-3 px-4 sm:px-6 py-3 border-t border-slate-100 hover:bg-slate-50">
                    <span class="font-semibold">{{ $plan->student_name }}</span>
                    <x-status-badge :status="$plan->status" />
                </a>
            @empty
                <x-empty-state>لا توجد خطط قيد المراجعة.</x-empty-state>
            @endforelse
        </section>

        <section class="card">
            <div class="card-body pb-2"><h2 class="card-title">التقييمات</h2></div>
            @foreach ($approvedWithoutEvaluation as $plan)
                <div class="flex items-center justify-between gap-3 px-4 sm:px-6 py-3 border-t border-slate-100">
                    <div>
                        <div class="font-semibold">{{ $plan->student_name }}</div>
                        <div class="text-xs text-slate-500">خطة معتمدة — جاهزة للتقييم</div>
                    </div>
                    <form method="POST" action="{{ route('evaluations.start', $plan) }}">@csrf
                        <button class="btn-primary btn-sm">بدء التقييم</button>
                    </form>
                </div>
            @endforeach
            @foreach ($openEvaluations as $plan)
                <div class="flex items-center justify-between gap-3 px-4 sm:px-6 py-3 border-t border-slate-100">
                    <div>
                        <div class="font-semibold">{{ $plan->student_name }}</div>
                        <x-status-badge :status="$plan->evaluation->status" />
                    </div>
                    <a href="{{ route('evaluations.edit', $plan->evaluation) }}" class="btn-secondary btn-sm">متابعة التقييم</a>
                </div>
            @endforeach
            @if ($approvedWithoutEvaluation->isEmpty() && $openEvaluations->isEmpty())
                <x-empty-state>لا توجد تقييمات مطلوبة حالياً.</x-empty-state>
            @endif
        </section>
    </div>
</x-app-layout>
