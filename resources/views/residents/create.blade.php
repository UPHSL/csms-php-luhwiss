@extends('residents.layout')
@section('title', 'Register Resident')
@section('content')
    <a class="back-link" href="{{ route('residents.index') }}">&larr; Back to Resident Management</a>
    <div class="page-heading"><div><p class="eyebrow">New community record</p><h1>Register Resident</h1>
        <p class="muted">Enter the resident's information. New residents are registered as Active.</p></div></div>
    <section class="panel form-panel" aria-label="Resident registration">
        <form method="POST" action="{{ route('residents.store') }}">
            @csrf
            @include('residents.fields')
            <div class="form-actions"><button type="submit">Register Resident</button><a href="{{ route('residents.index') }}">Cancel</a></div>
        </form>
    </section>
@endsection
