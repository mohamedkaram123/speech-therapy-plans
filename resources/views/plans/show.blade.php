<x-app-layout :title="'خطة '.$plan->student_name">
    <x-slot name="header">
        <div class="flex items-center gap-3">
            <h1 class="text-xl font-bold text-slate-800">خطة الطالب: {{ $plan->student_name }}</h1>
            <x-status-badge :status="$plan->status" />
        </div>
        <div class="flex flex-wrap gap-2">
            @can('update', $plan)<a href="{{ route('plans.edit', $plan) }}" class="btn-primary btn-sm">تحرير الخطة</a>@endcan
            @can('download', $plan)<a href="{{ route('plans.pdf', $plan) }}" class="btn-secondary btn-sm">تحميل PDF</a>@endcan
            @if($plan->evaluation)
                <a href="{{ route('evaluations.show', $plan->evaluation) }}" class="btn-secondary btn-sm">عرض التقييم</a>
            @else
                @can('create', [\App\Models\Evaluation::class, $plan])
                    <form method="POST" action="{{ route('evaluations.start', $plan) }}">@csrf<button class="btn-success btn-sm">بدء التقييم</button></form>
                @endcan
            @endif
        </div>
    </x-slot>

    @if($plan->status === \App\Enums\PlanStatus::Returned && $lastReturn && auth()->user()->isTeacher())
        <div class="rounded-lg border border-rose-200 bg-rose-50 px-4 py-3 text-sm text-rose-800 mb-5">
            <b>ملاحظة المشرف:</b> {{ $lastReturn->note }}
        </div>
    @endif

    <section class="card card-body mb-6 grid sm:grid-cols-2 lg:grid-cols-4 gap-4 text-sm">
        <div><div class="text-slate-500">الطالب</div><div class="font-semibold">{{ $plan->student_name }}</div></div>
        <div><div class="text-slate-500">المعلم</div><div class="font-semibold">{{ $plan->teacher_name }}</div></div>
        <div><div class="text-slate-500">الفترة</div><div>{{ $plan->period->label() }}</div></div>
        <div><div class="text-slate-500">تاريخ الميلاد (هجري)</div><div dir="ltr" class="text-start">{{ $plan->birth_date_hijri ?: '—' }}</div></div>
        <div><div class="text-slate-500">العام الدراسي</div><div dir="ltr" class="text-start">{{ $plan->academic_year }}</div></div>
        <div><div class="text-slate-500">الفصل</div><div>{{ $plan->semester->label() }}</div></div>
        <div><div class="text-slate-500">بداية الخطة (هجري)</div><div dir="ltr" class="text-start">{{ $plan->start_date_hijri }} هـ</div></div>
        <div><div class="text-slate-500">نهاية الخطة (هجري)</div><div dir="ltr" class="text-start">{{ $plan->end_date_hijri }} هـ</div></div>
        <div><div class="text-slate-500">عدد الجلسات</div><div>{{ $plan->sessions_count }}</div></div>
        <div><div class="text-slate-500">جلسات الأسبوع</div><div>{{ $plan->sessions_per_week }}</div></div>
        <div class="sm:col-span-2"><div class="text-slate-500">التشخيص</div><div>{{ $plan->diagnosis ?: '—' }}</div></div>
        @if($plan->approved_at)
            <div><div class="text-slate-500">تاريخ الاعتماد</div><x-date-cell :date="$plan->approved_at" /></div>
            <div><div class="text-slate-500">اعتمدها</div><div>{{ $plan->approver?->name }}</div></div>
        @endif
    </section>

    <div class="grid lg:grid-cols-3 gap-6">
        <div class="lg:col-span-2 space-y-4">
            <h2 class="card-title">أهداف الخطة ({{ $plan->goals->count() }})</h2>
            @forelse($goalsByDomain as $domain => $goals)
                <section class="card">
                    <div class="px-4 sm:px-6 py-3 bg-teal-50 text-teal-800 font-bold rounded-t-xl">{{ $domain }}</div>
                    @foreach($goals as $goal)
                        <article class="px-4 sm:px-6 py-4 border-t border-slate-100 text-sm">
                            <div class="flex flex-wrap items-center gap-2 mb-1">
                                <span class="badge bg-slate-200 text-slate-700">{{ $loop->iteration }}</span>
                                @if($goal->printed_code)<span class="badge bg-slate-100 text-slate-600" dir="ltr">{{ $goal->printed_code }}</span>@endif
                                @if($goal->level)<span class="badge bg-slate-100 text-slate-600">{{ $goal->level }}</span>@endif
                                @if($goal->subcategory)<span class="badge bg-slate-100 text-slate-600">{{ $goal->subcategory }}</span>@endif
                            </div>
                            <p class="font-semibold leading-7">{{ $goal->goal_text }}</p>
                            @if($goal->training_elements)<div class="mt-2"><span class="text-slate-500">عناصر التدريب:</span><div class="whitespace-pre-line leading-7">{{ $goal->training_elements }}</div></div>@endif
                            @if($goal->generalization)<div class="mt-2"><span class="text-slate-500">التعميم:</span><div class="whitespace-pre-line leading-7">{{ $goal->generalization }}</div></div>@endif
                            @if($goal->teacher_note)<div class="mt-2 rounded bg-amber-50 text-amber-900 px-3 py-2"><b>ملاحظة المعلم:</b> {{ $goal->teacher_note }}</div>@endif
                        </article>
                    @endforeach
                </section>
            @empty
                <div class="card"><x-empty-state>لم تتم إضافة أهداف بعد.</x-empty-state></div>
            @endforelse
        </div>

        <aside class="space-y-4">
            @can('submit', $plan)
                <section class="card card-body">
                    <h2 class="card-title mb-2">إرسال للمراجعة</h2>
                    <p class="text-sm text-slate-600 mb-3">بعد الإرسال لا يمكن تعديل الخطة إلا إذا أعادها المشرف.</p>
                    <form method="POST" action="{{ route('plans.submit', $plan) }}" onsubmit="return confirm('إرسال الخطة للمراجعة؟')">@csrf
                        <button class="btn-primary w-full">إرسال الخطة للمراجعة</button>
                    </form>
                </section>
            @endcan

            @can('review', $plan)
                <section class="card card-body border-teal-300">
                    <h2 class="card-title mb-2">مراجعة المشرف</h2>
                    <form method="POST">
                        @csrf
                        <label for="note" class="label">ملاحظات <span class="text-slate-400 font-normal">(مطلوبة عند الإعادة)</span></label>
                        <textarea id="note" name="note" rows="4" class="input">{{ old('note') }}</textarea>
                        <x-input-error :messages="$errors->get('note')" class="mt-1" />
                        <div class="mt-3 flex gap-2">
                            <button name="action" value="approve" formaction="{{ route('plans.approve', $plan) }}" class="btn-success flex-1"
                                    onclick="return confirm('اعتماد الخطة؟ لن يمكن تعديلها بعد ذلك.')">اعتماد</button>
                            <button name="action" value="return" formaction="{{ route('plans.return', $plan) }}" class="btn-danger flex-1">إعادة للتعديل</button>
                        </div>
                    </form>
                </section>
            @endcan

            @include('plans.partials.review-history', ['reviews' => $plan->reviews])
        </aside>
    </div>
</x-app-layout>
