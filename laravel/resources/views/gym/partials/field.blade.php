@php($fieldId = 'field-'.$name.'-'.uniqid())
@php($fieldValue = old($name, $value ?? ''))
<div class="dash-field">
<label for="{{ $fieldId }}">{{ $label }}</label>
@if(($type ?? 'text') === 'select')
<select name="{{ $name }}" id="{{ $fieldId }}" @required($required ?? false)><option value="">Choose...</option>@foreach($options as $key=>$text)<option value="{{ $key }}" @selected((string)$fieldValue === (string)$key)>{{ $text }}</option>@endforeach</select>
@elseif(($type ?? 'text') === 'textarea')
<textarea name="{{ $name }}" id="{{ $fieldId }}" rows="3" @required($required ?? false)>{{ $fieldValue }}</textarea>
@elseif(($type ?? 'text') === 'checkbox')
<input type="hidden" name="{{ $name }}" value="0"><label class="dash-check"><input id="{{ $fieldId }}" type="checkbox" name="{{ $name }}" value="1" @checked($fieldValue)> {{ $checkLabel ?? 'Enabled' }}</label>
@else
<input id="{{ $fieldId }}" name="{{ $name }}" type="{{ $type ?? 'text' }}" value="{{ ($type ?? '') === 'password' ? '' : $fieldValue }}" @required($required ?? false) @if(isset($step)) step="{{ $step }}" @endif>
@endif
@error($name)<small class="dash-error">{{ $message }}</small>@enderror
</div>
