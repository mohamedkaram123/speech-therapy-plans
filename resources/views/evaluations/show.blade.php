@php($pending = $evaluation->status === \App\Enums\EvaluationStatus::PendingReview)
<x-app-layout title="تقييم الخطة">
    <x-slot name="header">
        <div class="flex items-center gap-3">
            <h1 class="text-xl font-bold text-slate-800">تقييم الطالب: {{ $plan->student_name }}</h1>
            <x-status-badge :status="$evaluation->status" />
        </div>
        <div class="flex flex-wrap gap-2">
            @can('update', $evaluation)<a href="{{ route('evaluations.edit', $evaluation) }}" class="btn-primary btn-sm">تحرير التقييم</a>@endcan
            @can('download', $evaluation)<a href="{{ route('evaluations.pdf', $evaluation) }}" class="btn-secondary btn-sm">تحميل PDF</a>@endcan
            <a href="{{ route('plans.show', $plan) }}" class="btn-secondary btn-sm">عرض الخطة</a>
        </div>
    </x-slot>

    @if($evaluation->status === \App\Enums\EvaluationStatus::Returned && $lastReturn && auth()->user()->isTeacher())
        <div class="rounded-lg border border-rose-200 bg-rose-50 px-4 py-3 text-sm text-rose-800 mb-5">
            <b>ملاحظة المشرف:</b> {{ $lastReturn->note }}
        </div>
    @endif

    <section class="card card-body mb-6 grid sm:grid-cols-2 lg:grid-cols-4 gap-4 text-sm">
        <div><div class="text-slate-500">الطالب</div><div class="font-semibold">{{ $plan->student_name }}</div></div>
        <div><div class="text-slate-500">المعلم</div><div class="font-semibold">{{ $plan->teacher_name }}</div></div>
        <div><div class="text-slate-500">العام / الفصل</div><div>{{ $plan->academic_year }} — {{ $plan->semester->label() }}</div></div>
        <div><div class="text-slate-500">تاريخ إتمام التقييم (هجري)</div><div dir="ltr" class="text-start">{{ $evaluation->evaluation_completed_at_hijri ?: '—' }} @if($evaluation->evaluation_completed_at_hijri) هـ @endif</div></div>
    </section>

    <div class="grid lg:grid-cols-3 gap-6">
        <div class="lg:col-span-2 space-y-6">
            <section class="card">
                <div class="card-body pb-2"><h2 class="card-title">نتائج الأهداف</h2></div>
                <div class="table-wrap">
                    <table class="tbl">
                        <thead><tr><th>م</th><th>الهدف</th><th>النتيجة</th><th>ملاحظات</th></tr></thead>
                        <tbody>
                        @foreach($plan->goals as $goal)
                            @php($r = $results->get($goal->id))
                            <tr>
                                <td>{{ $loop->iteration }}</td>
                                <td class="min-w-[16rem]"><div class="text-xs text-teal-700 mb-0.5">{{ $goal->domain_name }}</div>{{ $goal->goal_text }}</td>
                                <td class="whitespace-nowrap">
                                    @if($r?->result)
                                        <span class="badge {{ match($r->result->value) { 'mastered' => 'bg-emerald-100 text-emerald-800', 'in_training' => 'bg-amber-100 text-amber-800', default => 'bg-rose-100 text-rose-800' } }}">{{ $r->result->label() }}</span>
                                    @else<span class="text-slate-400">لم يُقيَّم</span>@endif
                                </td>
                                <td>{{ $r?->note ?: '—' }}</td>
                            </tr>
                        @endforeach
                        </tbody>
                    </table>
                </div>
            </section>

            @if($evaluation->isApproved() || ! auth()->user()->can('review', $evaluation))
                @if(collect($reportFields)->keys()->contains(fn ($f) => filled($evaluation->{$f})))
                    <section class="card card-body space-y-4">
                        <h2 class="card-title">تقرير التقييم</h2>
                        @foreach($reportFields as $field => $label)
                            @if(filled($evaluation->{$field}))
                                <div><div class="text-sm font-semibold text-slate-600">{{ $label }}</div><p class="whitespace-pre-line leading-7">{{ $evaluation->{$field} }}</p></div>
                            @endif
                        @endforeach
                    </section>
                @endif
            @endif
        </div>

        <aside class="space-y-4">
            @can('review', $evaluation)
                <section class="card card-body border-teal-300">
                    <h2 class="card-title mb-3">مراجعة المشرف وتقرير التقييم</h2>
                    <form method="POST" class="space-y-3">
                        @csrf
                        @foreach($reportFields as $field => $label)
                            <div>
                                <label for="{{ $field }}" class="label">{{ $label }}</label>
                                <textarea id="{{ $field }}" name="{{ $field }}" rows="3" class="input">{{ old($field, $evaluation->{$field}) }}</textarea>
                                <x-input-error :messages="$errors->get($field)" class="mt-1" />
                            </div>
                        @endforeach
                        <div>
                            <label for="note" class="label">ملاحظات المراجعة <span class="text-slate-400 font-normal">(مطلوبة عند الإعادة)</span></label>
                            <textarea id="note" name="note" rows="3" class="input">{{ old('note') }}</textarea>
                            <x-input-error :messages="$errors->get('note')" class="mt-1" />
                        </div>
                        <div class="flex gap-2">
                            <button name="action" value="approve" formaction="{{ route('evaluations.approve', $evaluation) }}" class="btn-success flex-1"
                                    onclick="return confirm('اعتماد التقييم؟ لن يمكن تعديله بعد ذلك.')">اعتماد</button>
                            <button name="action" value="return" formaction="{{ route('evaluations.return', $evaluation) }}" class="btn-danger flex-1">إعادة للتعديل</button>
                        </div>
                    </form>
                </section>
            @endcan

            @include('plans.partials.review-history', ['reviews' => $evaluation->reviews])
        </aside>
    </div>
</x-app-layout>
