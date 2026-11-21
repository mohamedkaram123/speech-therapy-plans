@props(['date' => null])

@if($date)
    <span dir="ltr" class="whitespace-nowrap font-medium">{{ \App\Support\HijriDate::toHijri($date) }} هـ</span>
    <span dir="ltr" class="block text-xs text-slate-400">{{ $date->format('Y/m/d H:i') }} م</span>
@else
    <span class="text-slate-400">—</span>
@endif
