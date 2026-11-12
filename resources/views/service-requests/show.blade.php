@extends('residents.layout')
@section('title', 'Service Request Details')
@section('content')
    <a class="back-link" href="{{ route('service-requests.index') }}">&larr; Back to Service Request Management</a>
    <div class="page-heading"><div><p class="eyebrow">Community request</p><h1>Service Request Details</h1></div></div>
    @if ($errors->any())
        <div class="notice error-summary" role="alert"><ul>@foreach ($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div>
    @endif
    <section class="panel form-panel" aria-label="Service Request details">
        <dl class="request-details">
            @foreach (['id' => 'ID', 'resident_id' => 'Resident ID', 'service_type' => 'Service Type', 'description' => 'Description', 'date_requested' => 'Date Requested', 'status' => 'Status'] as $field => $label)
                <dt>{{ $label }}</dt>
                <dd class="{{ $field === 'description' ? 'request-description' : '' }}">@if ($field === 'status')<span class="badge status-{{ Str::slug($serviceRequest->status) }}">{{ $serviceRequest->status }}</span>@else{{ $serviceRequest->$field }}@endif</dd>
            @endforeach
        </dl>
        <div class="form-actions">@include('service-requests.actions')</div>
    </section>
@endsection
