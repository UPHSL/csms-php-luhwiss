@extends('residents.layout')
@section('title', 'Resident Management')
@section('content')
    <div class="page-heading">
        <div><p class="eyebrow">Community records</p><h1>Resident Management</h1>
            <p class="muted">Find, register, and manage residents in your community.</p></div>
        <a class="button" href="{{ route('residents.create') }}">Register Resident</a>
    </div>

    <section class="panel" aria-label="Resident search and listing">
        <form class="search-form" method="GET" action="{{ route('residents.index') }}">
            <div class="search-field">
                <label for="search">Search residents</label>
                <input id="search" type="search" name="q" value="{{ $search }}" placeholder="First or last name">
            </div>
            <button type="submit">Search</button>
            <a class="button secondary" href="{{ route('residents.index') }}">Clear search</a>
        </form>
        <div class="list-heading"><h2>Residents</h2><span class="muted">{{ $residents->count() }} {{ $residents->count() === 1 ? 'resident' : 'residents' }}</span></div>
        @if ($residents->isEmpty())
            <div class="empty-state"><h3>No Residents found.</h3><p class="muted">Try another name or register a new resident.</p></div>
        @else
            <div class="table-wrap">
                <table>
                    <caption class="sr-only">Persisted residents and available actions</caption>
                    <thead><tr>
                        <th scope="col">ID</th><th scope="col">Name</th><th scope="col">Address</th>
                        <th scope="col">Contact Number</th><th scope="col">Email</th><th scope="col">Status</th><th scope="col">Actions</th>
                    </tr></thead>
                    <tbody>
                    @foreach ($residents as $resident)
                        <tr>
                            <td data-label="ID">{{ $resident->id }}</td>
                            <td data-label="Name" class="resident-name">{{ $resident->first_name }} {{ $resident->last_name }}</td>
                            <td data-label="Address">{{ $resident->address }}</td>
                            <td data-label="Contact Number">{{ $resident->contact_number }}</td>
                            <td data-label="Email">{{ $resident->email }}</td>
                            <td data-label="Status"><span class="badge {{ $resident->status === 'Active' ? 'active' : 'inactive' }}">{{ $resident->status }}</span></td>
                            <td data-label="Actions"><div class="row-actions">
                                <a href="{{ route('residents.edit', $resident->id) }}" aria-label="Edit {{ $resident->first_name }} {{ $resident->last_name }}">Edit</a>
                                @if ($resident->status === 'Active')
                                    <form method="POST" action="{{ route('residents.deactivate', $resident->id) }}">
                                        @csrf
                                        <button class="text-button" type="submit" aria-label="Deactivate {{ $resident->first_name }} {{ $resident->last_name }}">Deactivate</button>
                                    </form>
                                @endif
                            </div></td>
                        </tr>
                    @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </section>
@endsection
