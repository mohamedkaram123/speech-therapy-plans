<x-app-layout title="بنك الأهداف">
    <x-slot name="header">
        <h1 class="text-xl font-bold text-slate-800">بنك الأهداف</h1>
        <span class="text-sm text-slate-500">{{ $goals->total() }} هدف</span>
    </x-slot>

    <form method="GET" class="card card-body mb-4 grid md:grid-cols-5 gap-3 items-end">
        <div class="md:col-span-2">
            <label class="label" for="q">بحث في نص الهدف</label>
            <input id="q" name="q" value="{{ request('q') }}" class="input" placeholder="كلمة أو جزء من الهدف">
        </div>
        <div>
            <label class="label" for="domain_id">المجال</label>
            <select id="domain_id" name="domain_id" class="input" onchange="this.form.level.value='';this.form.subcategory.value='';this.form.submit()">
                <option value="">كل المجالات</option>
                @foreach($domains as $d)<option value="{{ $d->id }}" @selected(request('domain_id') == $d->id)>{{ $d->name }}</option>@endforeach
            </select>
        </div>
        <div>
            <label class="label" for="level">المستوى</label>
            <select id="level" name="level" class="input" onchange="this.form.subcategory.value='';this.form.submit()">
                <option value="">الكل</option>
                @foreach($facets['levels'] as $l)<option value="{{ $l }}" @selected(request('level') === $l)>{{ $l }}</option>@endforeach
            </select>
        </div>
        <div>
            <label class="label" for="subcategory">التصنيف الفرعي</label>
            <select id="subcategory" name="subcategory" class="input" onchange="this.form.submit()">
                <option value="">الكل</option>
                @foreach($facets['subcategories'] as $s)<option value="{{ $s }}" @selected(request('subcategory') === $s)>{{ $s }}</option>@endforeach
            </select>
        </div>
        <div class="md:col-span-5 flex gap-2">
            <button class="btn-primary">بحث</button>
            <a href="{{ route('goals-bank.index') }}" class="btn-secondary">مسح التصفية</a>
        </div>
    </form>

    <div class="space-y-3">
        @forelse($goals as $goal)
            <article class="card card-body" x-data="{ open: false }">
                <div class="flex flex-wrap items-center gap-2 text-xs mb-2">
                    <span class="badge bg-teal-100 text-teal-800">{{ $goal->domain?->name }}</span>
                    @if($goal->level)<span class="badge bg-slate-100 text-slate-700">{{ $goal->level }}</span>@endif
                    @if($goal->subcategory)<span class="badge bg-slate-100 text-slate-700">{{ $goal->subcategory }}</span>@endif
                    @if($goal->printed_code)<span class="badge bg-slate-200 text-slate-700" dir="ltr">{{ $goal->printed_code }}</span>@endif
                </div>
                <p class="font-semibold text-slate-800 leading-7">{{ $goal->goal_text }}</p>
                @if($goal->training_elements || $goal->generalization)
                    <button type="button" class="text-sm text-teal-700 mt-2 text-start" @click="open = ! open" x-text="open ? 'إخفاء التفاصيل' : 'عرض عناصر التدريب والتعميم'"></button>
                    <div x-show="open" x-cloak class="mt-3 grid md:grid-cols-2 gap-3 text-sm">
                        @if($goal->training_elements)
                            <div class="rounded-lg bg-slate-50 p-3"><div class="font-semibold mb-1">عناصر التدريب</div><div class="whitespace-pre-line leading-7">{{ $goal->training_elements }}</div></div>
                        @endif
                        @if($goal->generalization)
                            <div class="rounded-lg bg-slate-50 p-3"><div class="font-semibold mb-1">التعميم</div><div class="whitespace-pre-line leading-7">{{ $goal->generalization }}</div></div>
                        @endif
                    </div>
                @endif
            </article>
        @empty
            <div class="card"><x-empty-state>لا توجد أهداف مطابقة. إذا كان البنك فارغاً شغّل أمر الاستيراد: <span dir="ltr">php artisan speech:import-goals-bank</span></x-empty-state></div>
        @endforelse
    </div>
    <div class="mt-4">{{ $goals->links() }}</div>
</x-app-layout>
