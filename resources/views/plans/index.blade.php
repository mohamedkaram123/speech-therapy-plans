<x-app-layout title="الخطط">
    <x-slot name="header"><h1 class="text-xl font-bold text-slate-800">{{ auth()->user()->isSupervisor() ? 'الخطط الفردية' : 'خططي' }}</h1></x-slot>

    <form method="GET" class="card card-body mb-4 grid sm:grid-cols-4 gap-3 items-end">
        <div class="sm:col-span-2">
            <label class="label" for="q">بحث باسم الطالب أو المعلم</label>
            <input id="q" name="q" value="{{ request('q') }}" class="input">
        </div>
        <div>
            <label class="label" for="status">الحالة</label>
            <select id="status" name="status" class="input">
                <option value="">الكل</option>
                @foreach(\App\Enums\PlanStatus::cases() as $s)<option value="{{ $s->value }}" @selected(request('status') === $s->value)>{{ $s->label() }}</option>@endforeach
            </select>
        </div>
        <button class="btn-primary">تصفية</button>
    </form>

    <div class="card">
        <div class="table-wrap">
            <table class="tbl">
                <thead><tr><th>م</th><th>الطالب</th><th>المعلم</th><th>العام / الفصل</th><th>الأهداف</th><th>الحالة</th><th>التقييم</th><th></th></tr></thead>
                <tbody>
                @forelse($plans as $plan)
                    <tr>
                        <td>{{ $plans->firstItem() + $loop->index }}</td>
                        <td class="font-semibold">{{ $plan->student_name }}</td>
                        <td>{{ $plan->teacher_name }}</td>
                        <td>{{ $plan->academic_year }} <span class="text-slate-500">— {{ $plan->semester->label() }}</span></td>
                        <td>{{ $plan->goals_count }}</td>
                        <td><x-status-badge :status="$plan->status" /></td>
                        <td>@if($plan->evaluation)<a href="{{ route('evaluations.show', $plan->evaluation) }}"><x-status-badge :status="$plan->evaluation->status" /></a>@else<span class="text-slate-400">—</span>@endif</td>
                        <td class="whitespace-nowrap">
                            <a href="{{ route('plans.show', $plan) }}" class="btn-secondary btn-sm">عرض</a>
                            @can('update', $plan)<a href="{{ route('plans.edit', $plan) }}" class="btn-primary btn-sm">تحرير</a>@endcan
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="8"><x-empty-state>لا توجد خطط.</x-empty-state></td></tr>
                @endforelse
                </tbody>
            </table>
        </div>
    </div>
    <div class="mt-4">{{ $plans->links() }}</div>
</x-app-layout>
