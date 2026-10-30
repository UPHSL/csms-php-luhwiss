@extends('residents.layout')
@section('title', 'Service Request Management')
@section('content')
    <div class="page-heading">
        <div><p class="eyebrow">Community services</p><h1>Service Request Management</h1>
            <p class="muted">Find, review, and process persisted Service Requests.</p></div>
        <a class="button" href="{{ route('service-requests.create') }}">Submit Service Request</a>
    </div>
    <section class="panel" aria-label="Service Requests">
        <form class="search-form" method="GET" action="{{ route('service-requests.index') }}">
            <div class="search-field"><label for="q">Search service requests</label>
                <input id="q" name="q" type="search" value="{{ $search }}" placeholder="Service type or description"></div>
            <div><label for="status">Status</label><select id="status" name="status">
                <option value="">All Statuses</option>
                @foreach (['Pending', 'In Progress', 'Completed', 'Cancelled'] as $option)
                    <option value="{{ $option }}" @selected($status === $option)>{{ $option }}</option>
                @endforeach
            </select></div>
            <button type="submit">Search / Filter</button>
            <a href="{{ route('service-requests.index') }}">Clear</a>
        </form>
        <div class="list-heading"><h2>Service Requests</h2><span class="muted">{{ $serviceRequests->count() }} {{ Str::plural('request', $serviceRequests->count()) }}</span></div>
        @if ($serviceRequests->isEmpty())
            <div class="empty-state"><p>No Service Requests found.</p></div>
        @else
            <div class="table-wrap"><table>
                <caption class="sr-only">Persisted Service Requests and available actions</caption>
                <thead><tr><th scope="col">ID</th><th scope="col">Resident ID</th><th scope="col">Service Type</th>
                    <th scope="col">Date Requested</th><th scope="col">Status</th><th scope="col">Available Actions</th></tr></thead>
                <tbody>@foreach ($serviceRequests as $serviceRequest)
                    <tr>
                        <td data-label="ID">{{ $serviceRequest->id }}</td>
                        <td data-label="Resident ID">{{ $serviceRequest->resident_id }}</td>
                        <td data-label="Service Type">{{ $serviceRequest->service_type }}</td>
                        <td data-label="Date Requested">{{ $serviceRequest->date_requested }}</td>
                        <td data-label="Status"><span class="badge status-{{ Str::slug($serviceRequest->status) }}">{{ $serviceRequest->status }}</span></td>
                        <td data-label="Available Actions">
                            <a href="{{ route('service-requests.show', $serviceRequest->id) }}" aria-label="View request {{ $serviceRequest->id }}">View details</a>
                            @include('service-requests.actions')
                        </td>
                    </tr>
                @endforeach</tbody>
            </table></div>
        @endif
    </section>
@endsection
