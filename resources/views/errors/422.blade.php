@extends('residents.layout')
@section('title', 'Invalid request')
@section('content')
    <section class="panel empty-state">
        <p class="eyebrow">422 &middot; Invalid request</p>
        <h1>Unsupported Service Request status filter.</h1>
        <p class="muted">Select All Statuses, Pending, In Progress, Completed, or Cancelled.</p>
        <a class="button" href="{{ route('service-requests.index') }}">Back to Service Request Management</a>
    </section>
@endsection
