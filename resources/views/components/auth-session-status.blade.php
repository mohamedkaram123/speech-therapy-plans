@props(['status'])

@if ($status)
    <div {{ $attributes->merge(['class' => 'font-medium text-sm text-teal-700']) }}>
        {{ $status }}
    </div>
@endif
