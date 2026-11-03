@extends('residents.layout')
@section('title', 'CSMS Dashboard')
@section('content')
    <div class="page-heading">
        <div><p class="eyebrow">Current operational summary</p><h1>CSMS Dashboard</h1>
            <p class="muted">Resident and Service Request information from current persisted records.</p></div>
    </div>

    <section class="dashboard-section" aria-labelledby="resident-summary">
        <h2 id="resident-summary">Resident Summary</h2>
        <dl class="metrics-grid">
            @foreach (['total' => 'Total Residents', 'active' => 'Active Residents', 'inactive' => 'Inactive Residents'] as $key => $label)
                <div class="panel metric"><dt>{{ $label }}</dt><dd class="metric-value">{{ $residents[$key] }}</dd></div>
            @endforeach
        </dl>
    </section>

    <section class="dashboard-section" aria-labelledby="request-summary">
        <h2 id="request-summary">Service Request Summary</h2>
        <dl class="metrics-grid">
            @foreach (['total' => 'Total Service Requests', 'pending' => 'Pending', 'in_progress' => 'In Progress', 'completed' => 'Completed', 'cancelled' => 'Cancelled'] as $key => $label)
                <div class="panel metric"><dt>{{ $label }}</dt><dd class="metric-value">{{ $serviceRequests[$key] }}</dd></div>
            @endforeach
        </dl>
    </section>

    <section class="dashboard-section" aria-labelledby="open-summary">
        <h2 id="open-summary">Open Service Requests</h2>
        <dl class="metrics-grid">
            <div class="panel metric"><dt>Pending + In Progress</dt><dd class="metric-value">{{ $serviceRequests['open'] }}</dd></div>
        </dl>
        <p class="muted">Completed and Cancelled requests are terminal and are excluded from Open.</p>
    </section>

    <section class="panel" aria-labelledby="recent-requests">
        <div class="list-heading"><h2 id="recent-requests">Recent Service Requests</h2>
            <a href="{{ route('service-requests.index') }}">View all Service Requests</a></div>
        @if ($recentServiceRequests->isEmpty())
            <div class="empty-state"><p>No Service Requests available.</p></div>
        @else
            <div class="table-wrap"><table>
                <caption class="sr-only">Five most recent Service Requests, ordered by date requested and ID descending</caption>
                <thead><tr><th scope="col">ID</th><th scope="col">Resident ID</th><th scope="col">Service Type</th>
                    <th scope="col">Date Requested</th><th scope="col">Status</th><th scope="col">Action</th></tr></thead>
                <tbody>@foreach ($recentServiceRequests as $serviceRequest)
                    <tr>
                        <td data-label="ID">{{ $serviceRequest->id }}</td>
                        <td data-label="Resident ID">{{ $serviceRequest->resident_id }}</td>
                        <td data-label="Service Type">{{ $serviceRequest->service_type }}</td>
                        <td data-label="Date Requested">{{ $serviceRequest->date_requested }}</td>
                        <td data-label="Status"><span class="badge status-{{ Str::slug($serviceRequest->status) }}">{{ $serviceRequest->status }}</span></td>
                        <td data-label="Action"><a href="{{ route('service-requests.show', $serviceRequest->id) }}" aria-label="View request {{ $serviceRequest->id }}">View details</a></td>
                    </tr>
                @endforeach</tbody>
            </table></div>
        @endif
    </section>
@endsection
