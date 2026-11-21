@props(['active' => false, 'supervisor' => false])

@php
if ($supervisor) {
    $classes = $active
        ? 'inline-flex items-center px-3 py-2 rounded-lg text-sm font-semibold bg-amber-500/20 text-amber-100'
        : 'inline-flex items-center px-3 py-2 rounded-lg text-sm font-medium text-slate-300 hover:bg-white/10 hover:text-white';
} else {
    $classes = $active
        ? 'inline-flex items-center px-3 py-2 rounded-lg text-sm font-semibold bg-teal-50 text-teal-800'
        : 'inline-flex items-center px-3 py-2 rounded-lg text-sm font-medium text-slate-600 hover:bg-slate-100 hover:text-slate-900';
}
@endphp

<a {{ $attributes->merge(['class' => $classes]) }}>{{ $slot }}</a>
