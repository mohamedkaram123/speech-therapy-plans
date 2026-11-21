@props(['name', 'label', 'value' => null, 'required' => false, 'example' => '1448/02/05'])

<div {{ $attributes->only('class') }}>
    <label for="{{ $name }}" class="label">
        {{ $label }}
        <span class="badge bg-teal-100 text-teal-800 ms-1">هجري</span>
        @if($required)<span class="text-rose-600">*</span>@endif
    </label>
    <input type="text" id="{{ $name }}" name="{{ $name }}" dir="ltr" inputmode="numeric" autocomplete="off"
           value="{{ old($name, $value) }}" placeholder="{{ $example }}" maxlength="10"
           class="input" @required($required)>
    <p class="hint">التاريخ بالتقويم الهجري، مثال: <span dir="ltr" class="font-mono">{{ $example }}</span></p>
    <x-input-error :messages="$errors->get($name)" class="mt-1" />
</div>
