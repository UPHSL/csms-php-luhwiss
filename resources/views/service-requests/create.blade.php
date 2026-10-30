@extends('residents.layout')
@section('title', 'Submit Service Request')
@section('content')
    <a class="back-link" href="{{ route('service-requests.index') }}">&larr; Back to Service Request Management</a>
    <div class="page-heading"><div><p class="eyebrow">New community request</p><h1>Submit Service Request</h1>
        <p class="muted">New requests begin as Pending. Only Active Residents are eligible.</p></div></div>
    <section class="panel form-panel" aria-label="Service Request submission">
        @if ($errors->any())
            <div class="notice error-summary" role="alert"><strong>Please correct the Service Request information.</strong>
                <ul>@foreach ($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul>
            </div>
        @endif
        <p class="form-note muted">All fields are required. <a href="{{ route('residents.index') }}">View Residents</a> to find the Resident ID.</p>
        <form method="POST" action="{{ route('service-requests.store') }}">
            @csrf
            <div class="fields-grid">
                @foreach (['resident_id' => 'Resident ID', 'service_type' => 'Service Type', 'description' => 'Description', 'date_requested' => 'Date Requested'] as $field => $label)
                    <div class="field {{ $field === 'description' ? 'full-width' : '' }}">
                        <label for="{{ $field }}">{{ $label }}</label>
                        @if ($field === 'description')
                            <textarea id="{{ $field }}" name="{{ $field }}" rows="5" required
                                @error($field) aria-invalid="true" aria-describedby="{{ $field }}-error" @enderror>{{ old($field) }}</textarea>
                        @else
                            <input id="{{ $field }}" name="{{ $field }}" type="{{ $field === 'date_requested' ? 'date' : 'text' }}"
                                value="{{ old($field) }}" required @if ($field === 'resident_id') inputmode="numeric" @endif
                                @error($field) aria-invalid="true" aria-describedby="{{ $field }}-error" @enderror>
                        @endif
                        @error($field)<p class="field-error" id="{{ $field }}-error">{{ $message }}</p>@enderror
                    </div>
                @endforeach
            </div>
            <div class="form-actions"><button type="submit">Submit Service Request</button>
                <a href="{{ route('service-requests.index') }}">Cancel</a></div>
        </form>
    </section>
@endsection
