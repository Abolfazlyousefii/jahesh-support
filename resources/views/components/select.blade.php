@props(['label', 'name', 'required' => false])
<div>
    <label class="form-label" for="{{ $name }}">{{ $label }} @if($required)<span class="text-red-600" aria-hidden="true">*</span>@endif</label>
    <select id="{{ $name }}" name="{{ $name }}" @required($required) {{ $attributes->class('form-control') }}>{{ $slot }}</select>
    @error($name)<p class="form-error">{{ $message }}</p>@enderror
</div>
