@props(['label', 'name', 'type' => 'text', 'value' => null, 'required' => false])
<div>
    <label class="form-label" for="{{ $name }}">{{ $label }} @if($required)<span class="text-red-600" aria-hidden="true">*</span>@endif</label>
    <input id="{{ $name }}" name="{{ $name }}" type="{{ $type }}" value="{{ old($name, $value) }}" @required($required) {{ $attributes->class('form-control') }}>
    @error($name)<p class="form-error">{{ $message }}</p>@enderror
</div>
