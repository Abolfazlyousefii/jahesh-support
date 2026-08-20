@props(['label', 'name', 'value' => null, 'required' => false])
<div>
    <label class="form-label" for="{{ $name }}">{{ $label }} @if($required)<span class="text-red-600" aria-hidden="true">*</span>@endif</label>
    <textarea id="{{ $name }}" name="{{ $name }}" @required($required) {{ $attributes->class('form-control min-h-32 resize-y') }}>{{ old($name, $value) }}</textarea>
    @error($name)<p class="form-error">{{ $message }}</p>@enderror
</div>
