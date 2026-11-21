@props(['status'])

@php
    $value = $status instanceof \BackedEnum ? $status->value : (string) $status;
    $label = method_exists($status, 'label') ? $status->label() : $value;
    $color = match ($value) {
        'draft' => 'bg-slate-100 text-slate-700',
        'pending_review' => 'bg-amber-100 text-amber-800',
        'returned' => 'bg-rose-100 text-rose-800',
        'approved' => 'bg-emerald-100 text-emerald-800',
        default => 'bg-slate-100 text-slate-700',
    };
@endphp

<span {{ $attributes->merge(['class' => "badge $color"]) }}>{{ $label }}</span>
