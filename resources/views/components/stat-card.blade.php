@props(['label', 'value', 'tone' => 'teal', 'href' => null])

@php
    $tones = [
        'teal' => 'text-teal-700 bg-teal-50',
        'amber' => 'text-amber-700 bg-amber-50',
        'rose' => 'text-rose-700 bg-rose-50',
        'slate' => 'text-slate-700 bg-slate-100',
        'emerald' => 'text-emerald-700 bg-emerald-50',
    ];
@endphp

<a href="{{ $href ?? '#' }}" class="card p-4 flex items-center justify-between gap-3 hover:border-teal-400 transition">
    <div>
        <div class="text-sm text-slate-500">{{ $label }}</div>
        <div class="text-3xl font-bold text-slate-800 mt-1">{{ $value }}</div>
    </div>
    <span class="h-10 w-10 rounded-full {{ $tones[$tone] ?? $tones['teal'] }}"></span>
</a>
