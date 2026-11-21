@if (session('success') || session('error') || session('warning') || $errors->any())
    <div class="space-y-2 mb-5 no-print" x-data="{ show: true }" x-show="show">
        @if (session('success'))
            <div class="flex items-start justify-between gap-3 rounded-lg border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-800">
                <span>{{ session('success') }}</span>
                <button type="button" @click="show = false" class="text-emerald-600" aria-label="إغلاق">&times;</button>
            </div>
        @endif
        @if (session('warning'))
            <div class="rounded-lg border border-amber-200 bg-amber-50 px-4 py-3 text-sm text-amber-800">{{ session('warning') }}</div>
        @endif
        @if (session('error'))
            <div class="flex items-start justify-between gap-3 rounded-lg border border-rose-200 bg-rose-50 px-4 py-3 text-sm text-rose-800">
                <span>{{ session('error') }}</span>
                <button type="button" @click="show = false" class="text-rose-600" aria-label="إغلاق">&times;</button>
            </div>
        @endif
        @if ($errors->any() && ! session('error'))
            <div class="rounded-lg border border-rose-200 bg-rose-50 px-4 py-3 text-sm text-rose-800">
                <p class="font-semibold mb-1">يرجى تصحيح الأخطاء التالية:</p>
                <ul class="list-disc ps-5 space-y-0.5">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif
    </div>
@endif
