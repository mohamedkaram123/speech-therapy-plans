@props(['active' => false])

@php
$classes = $active
    ? 'block w-full px-4 py-2.5 border-s-4 border-teal-600 text-start text-base font-semibold text-teal-800 bg-teal-50'
    : 'block w-full px-4 py-2.5 border-s-4 border-transparent text-start text-base font-medium text-slate-600 hover:bg-slate-50 hover:text-slate-900';
@endphp

<a {{ $attributes->merge(['class' => $classes]) }}>{{ $slot }}</a>
