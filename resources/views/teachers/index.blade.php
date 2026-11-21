<x-app-layout title="المعلمون">
    <x-slot name="header">
        <h1 class="text-xl font-bold text-slate-800">المعلمون</h1>
        <a href="{{ route('teachers.create') }}" class="btn-primary btn-sm">+ إضافة معلم</a>
    </x-slot>

    <form method="GET" class="card card-body mb-4 grid sm:grid-cols-4 gap-3 items-end">
        <div class="sm:col-span-2">
            <label class="label" for="q">بحث بالاسم أو البريد</label>
            <input id="q" name="q" value="{{ request('q') }}" class="input" placeholder="ابحث...">
        </div>
        <div>
            <label class="label" for="status">الحالة</label>
            <select id="status" name="status" class="input">
                <option value="">الكل</option>
                <option value="active" @selected(request('status') === 'active')>نشط</option>
                <option value="inactive" @selected(request('status') === 'inactive')>معطّل</option>
            </select>
        </div>
        <button class="btn-primary">تصفية</button>
    </form>

    <div class="card">
        <div class="table-wrap">
            <table class="tbl">
                <thead><tr>
                    <th>م</th><th>الاسم</th><th>البريد الإلكتروني</th><th>الطلاب</th><th>الخطط</th><th>الحالة</th><th></th>
                </tr></thead>
                <tbody>
                @forelse ($teachers as $teacher)
                    <tr>
                        <td>{{ $teachers->firstItem() + $loop->index }}</td>
                        <td class="font-semibold">{{ $teacher->name }}</td>
                        <td dir="ltr" class="text-start">{{ $teacher->email }}</td>
                        <td>{{ $teacher->students_count }}</td>
                        <td>{{ $teacher->therapy_plans_count }}</td>
                        <td>
                            @if($teacher->is_active)<span class="badge bg-emerald-100 text-emerald-800">نشط</span>
                            @else<span class="badge bg-slate-200 text-slate-600">معطّل</span>@endif
                        </td>
                        <td class="whitespace-nowrap">
                            <a href="{{ route('teachers.edit', $teacher) }}" class="btn-secondary btn-sm">تعديل</a>
                            <form method="POST" action="{{ route('teachers.toggle', $teacher) }}" class="inline">
                                @csrf @method('PATCH')
                                <button class="{{ $teacher->is_active ? 'btn-danger' : 'btn-success' }} btn-sm"
                                    @if($teacher->is_active) onclick="return confirm('تعطيل حساب هذا المعلم؟')" @endif>
                                    {{ $teacher->is_active ? 'تعطيل' : 'تفعيل' }}
                                </button>
                            </form>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="7"><x-empty-state>لا يوجد معلمون.</x-empty-state></td></tr>
                @endforelse
                </tbody>
            </table>
        </div>
    </div>
    <div class="mt-4">{{ $teachers->links() }}</div>
</x-app-layout>
