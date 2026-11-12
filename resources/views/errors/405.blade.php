@extends('residents.layout')
@section('title', 'Method not allowed')
@section('content')
    <section class="panel empty-state">
        <p class="eyebrow">405 &middot; Method not allowed</p>
        <h1>Method not allowed.</h1>
        <p class="muted">Return to the appropriate form or workflow action to perform this operation.</p>
        <a class="button" href="{{ route('dashboard') }}">Back to Dashboard</a>
    </section>
@endsection
