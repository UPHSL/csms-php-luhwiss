@if ($errors->any())
    <div class="notice error-summary" role="alert">
        <strong>Please correct the highlighted fields.</strong>
        <ul>@foreach ($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul>
    </div>
@endif
<p class="form-note muted">All fields are required.</p>
<div class="fields-grid">
    @foreach (['first_name' => 'First Name', 'last_name' => 'Last Name', 'address' => 'Address', 'contact_number' => 'Contact Number', 'email' => 'Email'] as $field => $label)
        <div class="field {{ $field === 'address' ? 'full-width' : '' }}">
            <label for="{{ $field }}">{{ $label }}</label>
            @if ($field === 'address')
                <textarea id="{{ $field }}" name="{{ $field }}" rows="3" autocomplete="street-address" required
                    @error($field) aria-invalid="true" aria-describedby="{{ $field }}-error" @enderror>{{ old($field, $resident?->$field ?? '') }}</textarea>
            @else
                <input id="{{ $field }}" name="{{ $field }}" type="{{ $field === 'email' ? 'email' : ($field === 'contact_number' ? 'tel' : 'text') }}"
                    value="{{ old($field, $resident?->$field ?? '') }}" required
                    autocomplete="{{ ['first_name' => 'given-name', 'last_name' => 'family-name', 'contact_number' => 'tel', 'email' => 'email'][$field] }}"
                    @error($field) aria-invalid="true" aria-describedby="{{ $field }}-error" @enderror>
            @endif
            @error($field)<p class="field-error" id="{{ $field }}-error">{{ $message }}</p>@enderror
        </div>
    @endforeach
</div>
