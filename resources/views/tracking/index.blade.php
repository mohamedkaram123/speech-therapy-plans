<x-app-layout title="كشف استلام الخطط">
    <x-slot name="header">
        <div>
            <h1 class="text-xl font-bold text-slate-800">كشف استلام خطط {{ config('speech.center_dept') }}</h1>
            <p class="text-sm text-slate-500">{{ config('speech.center_name') }} &middot; تم استلام {{ $received }} من {{ $total }}</p>
        </div>
        <button type="button" onclick="window.print()" class="btn-secondary btn-sm no-print">طباعة</button>
    </x-slot>

    <form method="GET" class="card card-body mb-5 grid sm:grid-cols-2 lg:grid-cols-6 gap-3 items-end no-print">
        <div>
            <label class="label" for="academic_year">العام الدراسي</label>
            <select id="academic_year" name="academic_year" class="input">
                <option value="">الكل</option>
                @foreach($years as $y)<option value="{{ $y }}" @selected($filters['academic_year'] === $y)>{{ $y }}</option>@endforeach
            </select>
        </div>
        <div>
            <label class="label" for="semester">الفصل</label>
            <select id="semester" name="semester" class="input">
                <option value="">الكل</option>
                @foreach(\App\Enums\Semester::cases() as $s)<option value="{{ $s->value }}" @selected($filters['semester'] === $s->value)>{{ $s->label() }}</option>@endforeach
            </select>
        </div>
        <div>
            <label class="label" for="period">الفترة</label>
            <select id="period" name="period" class="input">
                <option value="">الكل</option>
                @foreach(\App\Enums\StudentPeriod::cases() as $p)<option value="{{ $p->value }}" @selected($filters['period'] === $p->value)>{{ $p->label() }}</option>@endforeach
            </select>
        </div>
        <div>
            <label class="label" for="teacher_id">المعلم</label>
            <select id="teacher_id" name="teacher_id" class="input">
                <option value="">الكل</option>
                @foreach($teachers as $t)<option value="{{ $t->id }}" @selected($filters['teacher_id'] === $t->id)>{{ $t->name }}</option>@endforeach
            </select>
        </div>
        <div>
            <label class="label" for="plan_status">حالة الخطة</label>
            <select id="plan_status" name="plan_status" class="input">
                <option value="">الكل</option>
                <option value="none" @selected($filters['plan_status'] === 'none')>لم تُنشأ خطة</option>
                @foreach(\App\Enums\PlanStatus::cases() as $s)<option value="{{ $s->value }}" @selected($filters['plan_status'] === $s->value)>{{ $s->label() }}</option>@endforeach
            </select>
        </div>
        <button class="btn-primary">تصفية</button>
    </form>

    @forelse($sections as $key => $section)
        <section class="card mb-6">
            <div class="px-4 sm:px-6 py-3 bg-teal-700 text-white font-bold rounded-t-xl">
                الفترة {{ $section['label'] }} <span class="text-teal-100 font-normal text-sm">({{ $section['rows']->count() }})</span>
            </div>
            <div class="table-wrap">
                <table class="tbl">
                    <thead><tr>
                        <th class="w-12">م</th><th>أسماء المعلمات</th><th>أسماء الطلاب</th><th>تاريخ استلام الخطة</th>
                        <th>مراجعة الخطة</th><th>ملاحظات</th><th class="no-print">رابط</th>
                    </tr></thead>
                    <tbody>
                    @foreach($section['rows'] as $row)
                        @php($plan = $row['plan'])
                        <tr>
                            <td>{{ $loop->iteration }}</td>
                            <td>{{ $row['student']->teacher?->name ?? '—' }}</td>
                            <td class="font-semibold">{{ $row['student']->full_name }}</td>
                            <td><x-date-cell :date="$plan?->submitted_at" /></td>
                            <td>
                                @if($plan)
                                    <x-status-badge :status="$plan->status" />
                                @else
                                    <span class="badge bg-slate-100 text-slate-500">لم تُنشأ خطة</span>
                                @endif
                            </td>
                            <td class="max-w-xs text-slate-600">{{ $row['note'] ?: '—' }}</td>
                            <td class="no-print">
                                @if($plan)<a href="{{ route('plans.show', $plan) }}" class="text-teal-700 hover:underline whitespace-nowrap">فتح الخطة</a>@else<span class="text-slate-300">—</span>@endif
                            </td>
                        </tr>
                    @endforeach
                    </tbody>
                </table>
            </div>
        </section>
    @empty
        <div class="card"><x-empty-state>لا توجد بيانات مطابقة للتصفية.</x-empty-state></div>
    @endforelse
</x-app-layout>
