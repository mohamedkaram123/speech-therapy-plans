@props(['label', 'value', 'tone' => 'teal', 'href' => null, 'icon' => 'chart'])

@php
    $tones = [
        'teal' => 'text-teal-700 bg-teal-50',
        'amber' => 'text-amber-700 bg-amber-50',
        'rose' => 'text-rose-700 bg-rose-50',
        'slate' => 'text-slate-700 bg-slate-100',
        'emerald' => 'text-emerald-700 bg-emerald-50',
    ];
    $toneClass = $tones[$tone] ?? $tones['teal'];
@endphp

<a href="{{ $href ?? '#' }}" {{ $attributes->class(['card p-4 flex items-center justify-between gap-3 transition', 'hover:border-amber-400' => auth()->user()?->isSupervisor(), 'hover:border-teal-400' => ! auth()->user()?->isSupervisor()]) }}>
    <div class="min-w-0">
        <div class="text-sm text-slate-500 truncate">{{ $label }}</div>
        <div class="text-3xl font-bold text-slate-800 mt-1">{{ $value }}</div>
    </div>
    <span class="shrink-0 h-11 w-11 rounded-full {{ $toneClass }} inline-flex items-center justify-center" aria-hidden="true">
        @switch($icon)
            @case('users')
                {{-- teachers --}}
                <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M17 20v-2a4 4 0 0 0-4-4H7a4 4 0 0 0-4 4v2"/>
                    <circle cx="10" cy="7" r="3"/>
                    <path stroke-linecap="round" stroke-linejoin="round" d="M21 20v-2a3.5 3.5 0 0 0-2.5-3.35M16.5 4.2a3 3 0 0 1 0 5.6"/>
                </svg>
                @break
            @case('students')
                <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 14c-3.5 0-6.5 1.5-6.5 3.5V20h13v-2.5c0-2-3-3.5-6.5-3.5Z"/>
                    <circle cx="12" cy="8" r="3.5"/>
                </svg>
                @break
            @case('clipboard')
                <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M9 5h6a2 2 0 0 1 2 2v12a2 2 0 0 1-2 2H9a2 2 0 0 1-2-2V7a2 2 0 0 1 2-2Z"/>
                    <path stroke-linecap="round" stroke-linejoin="round" d="M9 5a2 2 0 0 1 2-2h2a2 2 0 0 1 2 2v1H9V5Z"/>
                    <path stroke-linecap="round" stroke-linejoin="round" d="M9 12h6M9 16h4"/>
                </svg>
                @break
            @case('clipboard-check')
                <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M9 5h6a2 2 0 0 1 2 2v12a2 2 0 0 1-2 2H9a2 2 0 0 1-2-2V7a2 2 0 0 1 2-2Z"/>
                    <path stroke-linecap="round" stroke-linejoin="round" d="M9 5a2 2 0 0 1 2-2h2a2 2 0 0 1 2 2v1H9V5Z"/>
                    <path stroke-linecap="round" stroke-linejoin="round" d="m9.5 13.5 2 2 3.5-4"/>
                </svg>
                @break
            @case('star')
                <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8">
                    <path stroke-linecap="round" stroke-linejoin="round" d="m12 3.5 2.4 4.9 5.4.8-3.9 3.8.9 5.4L12 15.8 7.2 18.4l.9-5.4-3.9-3.8 5.4-.8L12 3.5Z"/>
                </svg>
                @break
            @case('return')
                <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M9 14 4 9l5-5"/>
                    <path stroke-linecap="round" stroke-linejoin="round" d="M4 9h10.5a5.5 5.5 0 1 1 0 11H12"/>
                </svg>
                @break
            @default
                <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M4 19V9M10 19V5M16 19v-7M22 19V8"/>
                </svg>
        @endswitch
    </span>
</a>
