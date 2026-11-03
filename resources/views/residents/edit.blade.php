@extends('residents.layout')
@section('title', 'Edit Resident')
@section('content')
    <a class="back-link" href="{{ route('residents.index') }}">&larr; Back to Resident Management</a>
    <div class="page-heading"><div><p class="eyebrow">Community record</p><h1>Edit Resident</h1>
        <p class="muted">Update the resident's contact and personal information.</p></div></div>
    <section class="panel form-panel" aria-label="Resident information update">
        <div class="record-details"><strong>Resident #{{ $resident->id }}</strong>
            <span>Status: <span class="badge {{ $resident->status === 'Active' ? 'active' : 'inactive' }}">{{ $resident->status }}</span></span></div>
        <form method="POST" action="{{ route('residents.update', $resident->id) }}">
            @csrf
            @method('PUT')
            @include('residents.fields')
            <div class="form-actions"><button type="submit">Save changes</button><a href="{{ route('residents.index') }}">Cancel</a></div>
        </form>
    </section>
@endsection
