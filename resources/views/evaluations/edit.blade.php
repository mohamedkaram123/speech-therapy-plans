<x-app-layout title="تقييم الخطة">
    <x-slot name="header">
        <div>
            <h1 class="text-xl font-bold text-slate-800">تقييم الطالب: {{ $plan->student_name }}</h1>
            <p class="text-sm text-slate-500">{{ $plan->semester->label() }} {{ $plan->academic_year }} &middot; <x-status-badge :status="$evaluation->status" /></p>
        </div>
        <a href="{{ route('plans.show', $plan) }}" class="btn-secondary btn-sm">عرض الخطة</a>
    </x-slot>

    @if($evaluation->status === \App\Enums\EvaluationStatus::Returned && $lastReturn)
        <div class="rounded-lg border border-rose-200 bg-rose-50 px-4 py-3 text-sm text-rose-800 mb-5">
            <b>ملاحظة المشرف عند الإعادة:</b> {{ $lastReturn->note }}
        </div>
    @endif

    <form method="POST" action="{{ route('evaluations.update', $evaluation) }}" class="space-y-5">
        @csrf @method('PUT')

        <section class="card card-body max-w-md">
            <x-hijri-input name="evaluation_completed_at_hijri" label="تاريخ إتمام التقييم" :value="$evaluation->evaluation_completed_at_hijri" example="1448/06/25" />
        </section>

        <div class="space-y-3">
            @foreach($plan->goals as $i => $goal)
                @php($r = $results->get($goal->id))
                @php($current = old("results.$i.result", $r?->result?->value))
                <article class="card card-body">
                    <input type="hidden" name="results[{{ $i }}][therapy_plan_goal_id]" value="{{ $goal->id }}">
                    <div class="flex flex-wrap items-center gap-2 mb-2">
                        <span class="badge bg-teal-700 text-white">{{ $loop->iteration }}</span>
                        <span class="badge bg-teal-100 text-teal-800">{{ $goal->domain_name }}</span>
                        @if($goal->printed_code)<span class="badge bg-slate-200 text-slate-700" dir="ltr">{{ $goal->printed_code }}</span>@endif
                    </div>
                    <p class="font-semibold leading-7 mb-3">{{ $goal->goal_text }}</p>

                    <div class="flex flex-wrap gap-2 mb-3">
                        @foreach($options as $opt)
                            <label class="cursor-pointer">
                                <input type="radio" class="peer sr-only" name="results[{{ $i }}][result]" value="{{ $opt->value }}" @checked($current === $opt->value)>
                                <span class="inline-block rounded-lg border border-slate-300 px-4 py-2 text-sm font-semibold text-slate-600
                                    peer-checked:text-white peer-focus-visible:ring-2 peer-focus-visible:ring-teal-600
                                    {{ match($opt->value) { 'mastered' => 'peer-checked:bg-emerald-600 peer-checked:border-emerald-600', 'in_training' => 'peer-checked:bg-amber-500 peer-checked:border-amber-500', default => 'peer-checked:bg-rose-600 peer-checked:border-rose-600' } }}">
                                    {{ $opt->label() }}
                                </span>
                            </label>
                        @endforeach
                    </div>
                    <x-input-error :messages="$errors->get('results.'.$i.'.result')" class="mb-2" />

                    <label class="label">ملاحظات <span class="text-slate-400 font-normal">(اختياري)</span></label>
                    <textarea name="results[{{ $i }}][note]" rows="2" class="input">{{ old("results.$i.note", $r?->note) }}</textarea>
                </article>
            @endforeach
        </div>

        <div class="sticky bottom-0 -mx-4 sm:mx-0 bg-white/95 backdrop-blur border-t sm:border sm:rounded-xl border-slate-200 px-4 py-3 flex flex-wrap items-center gap-3 shadow-lg">
            <button name="action" value="save" class="btn-secondary">حفظ المسودة</button>
            <button name="action" value="submit" class="btn-primary" onclick="return confirm('إرسال التقييم للمراجعة؟ يجب تقييم جميع الأهداف.')">إرسال للمراجعة</button>
        </div>
    </form>
</x-app-layout>
