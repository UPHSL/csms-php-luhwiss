@extends('residents.layout')
@section('title', 'Not found')
@section('content')
    <section class="panel empty-state">
        <p class="eyebrow">404 &middot; Not found</p>
        <h1>{{ request()->is('service-requests/*') ? 'Service Request not found.' : (request()->is('residents/*') ? 'Resident not found.' : 'Page not found.') }}</h1>
        <p class="muted">The requested record or page could not be found.</p>
        @if (request()->is('service-requests/*'))
            <a class="button" href="{{ route('service-requests.index') }}">Back to Service Request Management</a>
        @else
            <a class="button" href="{{ route('residents.index') }}">Back to Resident Management</a>
        @endif
    </section>
@endsection
