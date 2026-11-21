@props(['name', 'label', 'type' => 'text', 'value' => null, 'required' => false, 'hint' => null, 'ltr' => false])

<div {{ $attributes->only('class') }}>
    <label for="{{ $name }}" class="label">{{ $label }} @if($required)<span class="text-rose-600">*</span>@endif</label>
    <input type="{{ $type }}" id="{{ $name }}" name="{{ $name }}"
           @if($type !== 'password') value="{{ old($name, $value) }}" @endif
           @if($ltr) dir="ltr" @endif
           class="input" @required($required) {{ $attributes->except('class') }}>
    @if($hint)<p class="hint">{{ $hint }}</p>@endif
    <x-input-error :messages="$errors->get($name)" class="mt-1" />
</div>
