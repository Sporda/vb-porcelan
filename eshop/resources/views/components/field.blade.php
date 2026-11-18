@props([
    'name',
    'label',
    'type' => 'text',
    'autocomplete' => null,
    'required' => false,
    'optional' => false,
    'hint' => null,
    'inputmode' => null,
    'maxlength' => null,
    'value' => null,
    'rows' => null,
])
@php
    $id = 'f-'.$name;
    $error = $errors->first($name);
    $describedBy = trim(($hint ? $id.'-hint ' : '').($error ? $id.'-error' : ''));
    $val = old($name, $value);
@endphp
<div {{ $attributes->merge(['class' => 'field'.($error ? ' has-error' : '')]) }}>
  <label for="{{ $id }}">{{ $label }}@if ($optional) <span class="opt-tag">nepovinné</span>@endif</label>
  @if ($type === 'textarea')
    <textarea id="{{ $id }}" name="{{ $name }}" rows="{{ $rows ?? 4 }}" @if ($maxlength) maxlength="{{ $maxlength }}" @endif @if ($required) required @endif @if ($describedBy) aria-describedby="{{ $describedBy }}" @endif @if ($error) aria-invalid="true" @endif>{{ $val }}</textarea>
  @else
    <input id="{{ $id }}" name="{{ $name }}" type="{{ $type }}" value="{{ $val }}" @if ($autocomplete) autocomplete="{{ $autocomplete }}" @endif @if ($inputmode) inputmode="{{ $inputmode }}" @endif @if ($maxlength) maxlength="{{ $maxlength }}" @endif @if ($required) required @endif @if ($describedBy) aria-describedby="{{ $describedBy }}" @endif @if ($error) aria-invalid="true" @endif>
  @endif
  @if ($hint)<p class="field-hint" id="{{ $id }}-hint">{{ $hint }}</p>@endif
  @if ($error)<p class="field-error" id="{{ $id }}-error">{{ $error }}</p>@endif
</div>
