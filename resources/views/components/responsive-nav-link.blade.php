@props(['active' => false, 'supervisor' => false])

@php
if ($supervisor) {
    $classes = $active
        ? 'block w-full px-4 py-2.5 border-s-4 border-amber-400 text-start text-base font-semibold text-amber-100 bg-white/10'
        : 'block w-full px-4 py-2.5 border-s-4 border-transparent text-start text-base font-medium text-slate-300 hover:bg-white/5 hover:text-white';
} else {
    $classes = $active
        ? 'block w-full px-4 py-2.5 border-s-4 border-teal-600 text-start text-base font-semibold text-teal-800 bg-teal-50'
        : 'block w-full px-4 py-2.5 border-s-4 border-transparent text-start text-base font-medium text-slate-600 hover:bg-slate-50 hover:text-slate-900';
}
@endphp

<a {{ $attributes->merge(['class' => $classes]) }}>{{ $slot }}</a>
