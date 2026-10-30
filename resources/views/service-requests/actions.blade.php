<div class="row-actions">
    @if ($serviceRequest->status === 'Pending')
        <form method="POST" action="{{ route('service-requests.start', $serviceRequest->id) }}">
            @csrf
            <button class="text-button" type="submit" aria-label="Start Processing request {{ $serviceRequest->id }}">Start Processing</button>
        </form>
    @elseif ($serviceRequest->status === 'In Progress')
        <form method="POST" action="{{ route('service-requests.complete', $serviceRequest->id) }}">
            @csrf
            <button class="text-button" type="submit" aria-label="Complete request {{ $serviceRequest->id }}">Complete</button>
        </form>
    @endif
    @if (in_array($serviceRequest->status, ['Pending', 'In Progress'], true))
        <form method="POST" action="{{ route('service-requests.cancel', $serviceRequest->id) }}">
            @csrf
            <button class="text-button" type="submit" aria-label="Cancel request {{ $serviceRequest->id }}">Cancel</button>
        </form>
    @else
        <span class="muted">Terminal state &mdash; no further workflow actions.</span>
    @endif
</div>
