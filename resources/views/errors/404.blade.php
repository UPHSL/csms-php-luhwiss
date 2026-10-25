@extends('residents.layout')
@section('title', 'Not found')
@section('content')
    <section class="panel empty-state">
        <p class="eyebrow">404 &middot; Not found</p>
        <h1>{{ request()->is('residents/*') ? 'Resident not found.' : 'Page not found.' }}</h1>
        <p class="muted">The requested record or page could not be found.</p>
        <a class="button" href="{{ route('residents.index') }}">Back to Resident Management</a>
    </section>
@endsection
