{{-- $reviews: collection of TherapyPlanReview|EvaluationReview with user loaded --}}
<section class="card">
    <div class="card-body pb-2"><h2 class="card-title">سجل المراجعة</h2></div>
    @forelse($reviews as $review)
        @php
            $tone = match($review->action->value) {
                'approved' => 'border-emerald-500', 'returned' => 'border-rose-500', default => 'border-teal-500',
            };
        @endphp
        <div class="px-4 sm:px-6 py-3 border-t border-slate-100 border-s-4 {{ $tone }}">
            <div class="flex flex-wrap items-center justify-between gap-2">
                <span class="font-semibold text-sm">{{ $review->action->label() }}
                    <span class="font-normal text-slate-500">— {{ $review->user?->name }}</span></span>
                <span class="text-xs text-slate-500" dir="ltr">{{ \App\Support\HijriDate::toHijri($review->created_at) }} هـ &middot; {{ $review->created_at->format('Y/m/d H:i') }}</span>
            </div>
            @if($review->note)<p class="mt-1 text-sm text-slate-700 whitespace-pre-line">{{ $review->note }}</p>@endif
        </div>
    @empty
        <x-empty-state>لا يوجد سجل بعد.</x-empty-state>
    @endforelse
</section>
