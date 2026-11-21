<x-app-layout title="التقييمات">
    <x-slot name="header"><h1 class="text-xl font-bold text-slate-800">{{ auth()->user()->isSupervisor() ? 'التقييمات' : 'تقييماتي' }}</h1></x-slot>

    @if($awaiting->isNotEmpty())
        <section class="card mb-6">
            <div class="card-body pb-2"><h2 class="card-title">خطط معتمدة جاهزة للتقييم</h2></div>
            @foreach($awaiting as $plan)
                <div class="flex items-center justify-between gap-3 px-4 sm:px-6 py-3 border-t border-slate-100">
                    <div><div class="font-semibold">{{ $plan->student_name }}</div><div class="text-xs text-slate-500">{{ $plan->semester->label() }} {{ $plan->academic_year }}</div></div>
                    <form method="POST" action="{{ route('evaluations.start', $plan) }}">@csrf<button class="btn-primary btn-sm">بدء التقييم</button></form>
                </div>
            @endforeach
        </section>
    @endif

    <form method="GET" class="card card-body mb-4 flex flex-wrap items-end gap-3">
        <div>
            <label class="label" for="status">الحالة</label>
            <select id="status" name="status" class="input">
                <option value="">الكل</option>
                @foreach(\App\Enums\EvaluationStatus::cases() as $s)<option value="{{ $s->value }}" @selected(request('status') === $s->value)>{{ $s->label() }}</option>@endforeach
            </select>
        </div>
        <button class="btn-primary">تصفية</button>
    </form>

    <div class="card">
        <div class="table-wrap">
            <table class="tbl">
                <thead><tr><th>م</th><th>الطالب</th><th>المعلم</th><th>العام / الفصل</th><th>الحالة</th><th>تاريخ الإرسال</th><th></th></tr></thead>
                <tbody>
                @forelse($evaluations as $ev)
                    <tr>
                        <td>{{ $evaluations->firstItem() + $loop->index }}</td>
                        <td class="font-semibold">{{ $ev->therapyPlan->student_name }}</td>
                        <td>{{ $ev->therapyPlan->teacher_name }}</td>
                        <td>{{ $ev->therapyPlan->academic_year }} <span class="text-slate-500">— {{ $ev->therapyPlan->semester->label() }}</span></td>
                        <td><x-status-badge :status="$ev->status" /></td>
                        <td><x-date-cell :date="$ev->submitted_at" /></td>
                        <td class="whitespace-nowrap">
                            <a href="{{ route('evaluations.show', $ev) }}" class="btn-secondary btn-sm">عرض</a>
                            @can('update', $ev)<a href="{{ route('evaluations.edit', $ev) }}" class="btn-primary btn-sm">تحرير</a>@endcan
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="7"><x-empty-state>لا توجد تقييمات.</x-empty-state></td></tr>
                @endforelse
                </tbody>
            </table>
        </div>
    </div>
    <div class="mt-4">{{ $evaluations->links() }}</div>
</x-app-layout>
