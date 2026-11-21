<x-app-layout title="محرر أهداف الخطة">
    <x-slot name="header">
        <div>
            <h1 class="text-xl font-bold text-slate-800">محرر الخطة: {{ $plan->student_name }}</h1>
            <p class="text-sm text-slate-500">{{ $plan->period->label() }} &middot; <x-status-badge :status="$plan->status" /></p>
        </div>
        <a href="{{ route('plans.show', $plan) }}" class="btn-secondary btn-sm">عرض الخطة</a>
    </x-slot>

    @if($plan->status === \App\Enums\PlanStatus::Returned && $lastReturn)
        <div class="rounded-lg border border-rose-200 bg-rose-50 px-4 py-3 text-sm text-rose-800 mb-5">
            <b>ملاحظة المشرف عند الإعادة:</b> {{ $lastReturn->note }}
        </div>
    @endif

    <div x-data="goalsEditor(@js($config))" class="space-y-5">
        {{-- Plan data --}}
        <section class="card card-body">
            <h2 class="card-title mb-3">بيانات الخطة</h2>
            <div class="grid sm:grid-cols-2 lg:grid-cols-3 gap-4">
                <div>
                    <label class="label">العام الدراسي <span class="badge bg-teal-100 text-teal-800 ms-1">هجري</span></label>
                    <input type="text" x-model="plan.academic_year" dir="ltr" class="input" placeholder="1448-1449">
                </div>
                <div>
                    <label class="label">الفصل الدراسي</label>
                    <select x-model="plan.semester" class="input">
                        @foreach($semesters as $s)<option value="{{ $s->value }}">{{ $s->label() }}</option>@endforeach
                    </select>
                </div>
                <div></div>
                <div>
                    <label class="label">تاريخ بداية الخطة <span class="badge bg-teal-100 text-teal-800 ms-1">هجري</span></label>
                    <input type="text" x-model="plan.start_date_hijri" dir="ltr" inputmode="numeric" maxlength="10" class="input" placeholder="1448/02/05">
                    <p class="hint">مثال: <span dir="ltr">1448/02/05</span></p>
                </div>
                <div>
                    <label class="label">تاريخ نهاية الخطة <span class="badge bg-teal-100 text-teal-800 ms-1">هجري</span></label>
                    <input type="text" x-model="plan.end_date_hijri" dir="ltr" inputmode="numeric" maxlength="10" class="input" placeholder="1448/06/20">
                    <p class="hint">مثال: <span dir="ltr">1448/06/20</span></p>
                </div>
                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <label class="label">عدد الجلسات</label>
                        <input type="number" min="1" max="500" x-model="plan.sessions_count" dir="ltr" class="input">
                    </div>
                    <div>
                        <label class="label">جلسات الأسبوع</label>
                        <input type="number" min="1" max="7" x-model="plan.sessions_per_week" dir="ltr" class="input">
                    </div>
                </div>
            </div>
        </section>

        {{-- Feedback --}}
        <div x-show="errors.length" x-cloak class="rounded-lg border border-rose-200 bg-rose-50 px-4 py-3 text-sm text-rose-800">
            <ul class="list-disc ps-5 space-y-0.5"><template x-for="e in errors"><li x-text="e"></li></template></ul>
        </div>

        {{-- Mobile tabs --}}
        <div class="lg:hidden grid grid-cols-2 gap-2">
            <button type="button" @click="tab = 'plan'" :class="tab === 'plan' ? 'btn-primary' : 'btn-secondary'">أهداف الخطة (<span x-text="goals.length"></span>)</button>
            <button type="button" @click="tab = 'bank'" :class="tab === 'bank' ? 'btn-primary' : 'btn-secondary'">بنك الأهداف</button>
        </div>

        <div class="grid lg:grid-cols-12 gap-5 items-start">
            {{-- Plan goals --}}
            <section class="lg:col-span-7 space-y-3" :class="tab === 'plan' ? '' : 'hidden lg:block'">
                <div class="flex items-center justify-between">
                    <h2 class="card-title">أهداف الخطة (<span x-text="goals.length"></span>)</h2>
                    <button type="button" class="btn-secondary btn-sm" @click="addCustom()">+ هدف مخصص</button>
                </div>

                <div x-show="goals.length === 0" class="card"><x-empty-state>لم تضف أي هدف بعد. اختر أهدافاً من بنك الأهداف.</x-empty-state></div>

                <template x-for="(goal, i) in goals" :key="goal.uid">
                    <article class="card card-body space-y-3">
                        <div class="flex flex-wrap items-center gap-2">
                            <span class="badge bg-teal-700 text-white" x-text="i + 1"></span>
                            <template x-if="goal.bank_goal_id">
                                <span class="badge bg-teal-100 text-teal-800" x-text="goal.domain_name"></span>
                            </template>
                            <span x-show="goal.level" class="badge bg-slate-100 text-slate-700" x-text="goal.level"></span>
                            <span x-show="goal.subcategory" class="badge bg-slate-100 text-slate-700" x-text="goal.subcategory"></span>
                            <span x-show="goal.printed_code" class="badge bg-slate-200 text-slate-700" dir="ltr" x-text="goal.printed_code"></span>
                            <span x-show="! goal.bank_goal_id" class="badge bg-amber-100 text-amber-800">هدف مخصص</span>

                            <div class="ms-auto flex gap-1">
                                <button type="button" class="btn-secondary btn-sm" @click="move(i, -1)" :disabled="i === 0" title="تحريك لأعلى">↑</button>
                                <button type="button" class="btn-secondary btn-sm" @click="move(i, 1)" :disabled="i === goals.length - 1" title="تحريك لأسفل">↓</button>
                                <button type="button" class="btn-danger btn-sm" @click="remove(i)">إزالة</button>
                            </div>
                        </div>

                        <div x-show="! goal.bank_goal_id">
                            <label class="label">المجال</label>
                            <input type="text" x-model="goal.domain_name" list="domain-list" class="input" placeholder="اسم المجال">
                        </div>

                        <div>
                            <label class="label">نص الهدف</label>
                            <textarea x-model="goal.goal_text" rows="3" class="input leading-7"></textarea>
                        </div>

                        <div>
                            <button type="button" class="text-sm text-teal-700" @click="goal.open = ! goal.open"
                                    x-text="goal.open ? 'إخفاء عناصر التدريب والتعميم' : 'عرض عناصر التدريب والتعميم'"></button>
                            <div x-show="goal.open" x-cloak class="mt-2 grid md:grid-cols-2 gap-3 text-sm">
                                <div>
                                    <div class="font-semibold mb-1">عناصر التدريب</div>
                                    <textarea x-model="goal.training_elements" rows="4" class="input" :readonly="!! goal.bank_goal_id" :class="goal.bank_goal_id ? 'bg-slate-50' : ''"></textarea>
                                </div>
                                <div>
                                    <div class="font-semibold mb-1">التعميم</div>
                                    <textarea x-model="goal.generalization" rows="4" class="input" :readonly="!! goal.bank_goal_id" :class="goal.bank_goal_id ? 'bg-slate-50' : ''"></textarea>
                                </div>
                            </div>
                        </div>

                        <div>
                            <label class="label">ملاحظة المعلم <span class="text-slate-400 font-normal">(اختياري)</span></label>
                            <textarea x-model="goal.teacher_note" rows="2" class="input"></textarea>
                        </div>
                    </article>
                </template>
                <datalist id="domain-list">
                    @foreach($config['domains'] as $d)<option value="{{ $d->name }}"></option>@endforeach
                </datalist>
            </section>

            {{-- Bank browser --}}
            <aside class="lg:col-span-5 lg:sticky lg:top-4" :class="tab === 'bank' ? '' : 'hidden lg:block'">
                <div class="card">
                    <div class="card-body pb-3 space-y-3 border-b border-slate-100">
                        <h2 class="card-title">بنك الأهداف</h2>
                        <input type="search" x-model="filters.q" @input="onSearchInput()" class="input" placeholder="ابحث في نص الهدف...">
                        <div class="grid grid-cols-1 sm:grid-cols-3 lg:grid-cols-1 xl:grid-cols-3 gap-2">
                            <select x-model="filters.domain_id" @change="onDomainChange()" class="input">
                                <option value="">كل المجالات</option>
                                <template x-for="d in domains" :key="d.id"><option :value="d.id" x-text="d.name"></option></template>
                            </select>
                            <select x-model="filters.level" @change="onLevelChange()" class="input">
                                <option value="">كل المستويات</option>
                                <template x-for="l in facets.levels" :key="l"><option :value="l" x-text="l"></option></template>
                            </select>
                            <select x-model="filters.subcategory" @change="loadBank(1)" class="input">
                                <option value="">كل التصنيفات</option>
                                <template x-for="s in facets.subcategories" :key="s"><option :value="s" x-text="s"></option></template>
                            </select>
                        </div>
                        <div class="flex items-center justify-between text-xs text-slate-500">
                            <label class="inline-flex items-center gap-1.5">
                                <input type="checkbox" x-model="showDetails" class="rounded border-slate-300 text-teal-700 focus:ring-teal-600"> إظهار عناصر التدريب والتعميم
                            </label>
                            <button type="button" class="text-teal-700" @click="resetFilters()">مسح التصفية</button>
                        </div>
                    </div>

                    <div class="max-h-[70vh] overflow-y-auto divide-y divide-slate-100">
                        <p x-show="loading" class="p-4 text-sm text-slate-500">جارٍ التحميل...</p>
                        <p x-show="loadError" x-text="loadError" class="p-4 text-sm text-rose-700"></p>
                        <p x-show="! loading && ! loadError && results.length === 0" class="p-4 text-sm text-slate-500">لا توجد أهداف مطابقة.</p>

                        <template x-for="g in results" :key="g.id">
                            <div class="p-4 text-sm space-y-2">
                                <div class="flex flex-wrap gap-1.5 text-xs">
                                    <span class="badge bg-teal-100 text-teal-800" x-text="g.domain_name"></span>
                                    <span x-show="g.level" class="badge bg-slate-100 text-slate-700" x-text="g.level"></span>
                                    <span x-show="g.subcategory" class="badge bg-slate-100 text-slate-700" x-text="g.subcategory"></span>
                                    <span x-show="g.printed_code" class="badge bg-slate-200 text-slate-700" dir="ltr" x-text="g.printed_code"></span>
                                </div>
                                <p class="font-semibold leading-7" x-text="g.goal_text"></p>
                                <div x-show="showDetails" class="space-y-2">
                                    <div x-show="g.training_elements" class="rounded-lg bg-slate-50 p-2.5">
                                        <div class="font-semibold text-xs text-slate-600 mb-0.5">عناصر التدريب</div>
                                        <div class="whitespace-pre-line leading-6" x-text="g.training_elements"></div>
                                    </div>
                                    <div x-show="g.generalization" class="rounded-lg bg-slate-50 p-2.5">
                                        <div class="font-semibold text-xs text-slate-600 mb-0.5">التعميم</div>
                                        <div class="whitespace-pre-line leading-6" x-text="g.generalization"></div>
                                    </div>
                                </div>
                                <button type="button" class="btn-sm w-full" :class="inPlan(g.id) ? 'btn-secondary' : 'btn-primary'"
                                        :disabled="inPlan(g.id)" @click="addFromBank(g)" x-text="inPlan(g.id) ? 'مضاف إلى الخطة ✓' : '+ إضافة إلى الخطة'"></button>
                            </div>
                        </template>
                    </div>

                    <div class="flex items-center justify-between px-4 py-2.5 border-t border-slate-100 text-xs text-slate-500">
                        <button type="button" class="btn-secondary btn-sm" :disabled="meta.current_page <= 1 || loading" @click="loadBank(meta.current_page - 1)">السابق</button>
                        <span><span x-text="meta.total"></span> هدف &middot; صفحة <span x-text="meta.current_page"></span> / <span x-text="meta.last_page"></span></span>
                        <button type="button" class="btn-secondary btn-sm" :disabled="meta.current_page >= meta.last_page || loading" @click="loadBank(meta.current_page + 1)">التالي</button>
                    </div>
                </div>
            </aside>
        </div>

        {{-- Sticky action bar --}}
        <div class="sticky bottom-0 -mx-4 sm:mx-0 bg-white/95 backdrop-blur border-t sm:border sm:rounded-xl border-slate-200 px-4 py-3 flex flex-wrap items-center gap-3 shadow-lg">
            <button type="button" class="btn-secondary" @click="save()" :disabled="saving || submitting">
                <span x-text="saving ? 'جارٍ الحفظ...' : 'حفظ المسودة'"></span>
            </button>
            <button type="button" class="btn-primary" @click="submit()" :disabled="saving || submitting">إرسال للمراجعة</button>
            <span class="text-sm text-slate-500">
                <span x-show="dirty" class="text-amber-700">توجد تغييرات غير محفوظة</span>
                <span x-show="! dirty && savedAt" x-cloak class="text-emerald-700">تم الحفظ <span x-text="savedAt"></span></span>
            </span>
            <span class="ms-auto text-sm text-slate-500"><span x-text="goals.length"></span> هدف</span>
        </div>
    </div>
</x-app-layout>
