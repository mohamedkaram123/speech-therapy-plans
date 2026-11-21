@php($isSup = auth()->user()->isSupervisor())
<x-app-layout :title="$isSup ? 'الطلاب' : 'طلابي'">
    <x-slot name="header">
        <h1 class="text-xl font-bold text-slate-800">{{ $isSup ? 'الطلاب' : 'طلابي' }}</h1>
        @can('create', \App\Models\Student::class)
            <a href="{{ route('students.create') }}" class="btn-primary btn-sm">+ إضافة طالب</a>
        @endcan
    </x-slot>

    <form method="GET" class="card card-body mb-4 grid sm:grid-cols-5 gap-3 items-end">
        <div class="{{ $isSup ? 'sm:col-span-2' : 'sm:col-span-3' }}">
            <label class="label" for="q">بحث بالاسم</label>
            <input id="q" name="q" value="{{ request('q') }}" class="input" placeholder="اسم الطالب">
        </div>
        @if($isSup)
            <div>
                <label class="label" for="teacher_id">المعلم</label>
                <select id="teacher_id" name="teacher_id" class="input">
                    <option value="">الكل</option>
                    @foreach($teachers as $t)<option value="{{ $t->id }}" @selected(request('teacher_id') == $t->id)>{{ $t->name }}</option>@endforeach
                </select>
            </div>
        @endif
        <div>
            <label class="label" for="period">الفترة</label>
            <select id="period" name="period" class="input">
                <option value="">الكل</option>
                @foreach(\App\Enums\StudentPeriod::cases() as $p)<option value="{{ $p->value }}" @selected(request('period') === $p->value)>{{ $p->label() }}</option>@endforeach
            </select>
        </div>
        <button class="btn-primary">تصفية</button>
    </form>

    <div class="card">
        <div class="table-wrap">
            <table class="tbl">
                <thead><tr>
                    <th>م</th><th>اسم الطالب</th>@if($isSup)<th>المعلم</th>@endif<th>الفترة</th><th>تاريخ الميلاد (هجري)</th><th>آخر خطة</th><th></th>
                </tr></thead>
                <tbody>
                @forelse ($students as $student)
                    @php($plan = $student->therapyPlans->first())
                    <tr>
                        <td>{{ $students->firstItem() + $loop->index }}</td>
                        <td class="font-semibold"><a href="{{ route('students.show', $student) }}" class="hover:text-teal-700">{{ $student->full_name }}</a></td>
                        @if($isSup)<td>{{ $student->teacher?->name }}</td>@endif
                        <td>{{ $student->period->label() }}</td>
                        <td dir="ltr" class="text-start">{{ $student->birth_date_hijri ?: '—' }}</td>
                        <td>
                            @if($plan)
                                <a href="{{ route('plans.show', $plan) }}"><x-status-badge :status="$plan->status" /></a>
                            @else
                                <span class="text-slate-400">لا توجد</span>
                            @endif
                        </td>
                        <td class="whitespace-nowrap">
                            @can('create', \App\Models\TherapyPlan::class)
                                <a href="{{ route('plans.create', $student) }}" class="btn-primary btn-sm">إنشاء خطة</a>
                            @endcan
                            @can('update', $student)
                                <a href="{{ route('students.edit', $student) }}" class="btn-secondary btn-sm">تعديل</a>
                            @endcan
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="7"><x-empty-state>لا يوجد طلاب.</x-empty-state></td></tr>
                @endforelse
                </tbody>
            </table>
        </div>
    </div>
    <div class="mt-4">{{ $students->links() }}</div>
</x-app-layout>
