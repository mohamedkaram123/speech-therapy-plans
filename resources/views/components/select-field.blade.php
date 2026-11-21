@props(['name', 'label', 'options', 'value' => null, 'required' => false, 'placeholder' => null])

<div {{ $attributes->only('class') }}>
    <label for="{{ $name }}" class="label">{{ $label }} @if($required)<span class="text-rose-600">*</span>@endif</label>
    <select id="{{ $name }}" name="{{ $name }}" class="input" @required($required)>
        @if($placeholder !== null)<option value="">{{ $placeholder }}</option>@endif
        @foreach($options as $k => $text)
            <option value="{{ $k }}" @selected((string) old($name, $value) === (string) $k)>{{ $text }}</option>
        @endforeach
    </select>
    <x-input-error :messages="$errors->get($name)" class="mt-1" />
</div>
