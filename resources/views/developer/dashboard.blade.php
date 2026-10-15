@extends('layouts.app')

@section('title', 'Developer Inbox — LGICT')

@include('developer.partials.nav')

@section('hero-title')
    <h1>DEVELOPER <em>INBOX</em></h1>
@endsection
@section('hero-subtitle', 'System concerns sent by ICT staff. Acknowledge them, then close them with a note.')

@section('hero-stats')
    <div class="d-flex gap-2 flex-wrap">
        <div class="stat-pill warn"><span class="num">{{ $counts['open'] }}</span><span class="lbl">Open</span></div>
        <div class="stat-pill info"><span class="num">{{ $counts['acknowledged'] }}</span><span class="lbl">Acknowledged</span></div>
        <div class="stat-pill"><span class="num">{{ $counts['closed'] }}</span><span class="lbl">Closed</span></div>
    </div>
@endsection

@section('sidebar')
    <div class="sidebar-card mb-3">
        <div class="sidebar-head">Concerns</div>
        <ul class="list-group sidebar-menu rounded-0">
            @foreach(['open' => ['Open', 'bi-inbox'], 'acknowledged' => ['Acknowledged', 'bi-eye'], 'closed' => ['Closed', 'bi-check2-circle']] as $key => [$label, $icon])
                <li class="list-group-item {{ $tab === $key ? 'active' : '' }}">
                    <a href="{{ route('developer.dashboard', ['tab' => $key]) }}" class="d-flex justify-content-between align-items-center text-decoration-none w-100">
                        <span><i class="bi {{ $icon }} me-2"></i>{{ $label }}</span>
                        <span class="badge-count">{{ $counts[$key] }}</span>
                    </a>
                </li>
            @endforeach
        </ul>
    </div>
@endsection

@section('content')
    @include('developer.partials.styles')
    @include('developer.partials.flash')

    <div class="d-flex flex-column gap-3">
        @forelse($concerns as $concern)
            <div class="ticket-card dc-card urgency-{{ $concern->urgency }}">
                <div class="d-flex flex-wrap align-items-center justify-content-between gap-2">
                    <div class="d-flex align-items-center gap-2 flex-wrap">
                        <span class="ticket-id">#{{ $concern->reference }}</span>
                        <span class="dc-chip dc-urgency-{{ $concern->urgency }}">{{ $concern->urgency }}</span>
                        <span class="dc-chip" style="background:#f1efe9;color:#555">{{ $concern->category }}</span>
                    </div>
                    <span class="dc-chip dc-status-{{ $concern->status }}">{{ $concern->status }}</span>
                </div>

                <div class="dc-subject">{{ $concern->subject }}</div>
                <div class="dc-details">{{ $concern->details }}</div>

                <div class="dc-meta">
                    <span><i class="bi bi-person me-1"></i>{{ $concern->submitter->name ?? 'Unknown' }}@if($concern->submitter?->role) · {{ $concern->submitter->role->role_name }}@endif</span>
                    <span><i class="bi bi-clock me-1"></i>Sent {{ $concern->created_at->timezone('Asia/Manila')->format('M j, Y g:i A') }}</span>
                    @if($concern->acknowledged_at)
                        <span><i class="bi bi-eye me-1"></i>Acknowledged {{ $concern->acknowledged_at->timezone('Asia/Manila')->format('M j, g:i A') }}</span>
                    @endif
                    @if($concern->closed_at)
                        <span><i class="bi bi-check2-circle me-1"></i>Closed {{ $concern->closed_at->timezone('Asia/Manila')->format('M j, g:i A') }}</span>
                    @endif
                </div>

                @if($concern->closing_note)
                    <div class="dc-note"><strong>Closing note:</strong> {{ $concern->closing_note }}</div>
                @endif

                @if($concern->status !== \App\Models\DeveloperConcern::CLOSED)
                    <div class="d-flex gap-2 mt-3">
                        @if($concern->status === \App\Models\DeveloperConcern::OPEN)
                            <form method="POST" action="{{ route('developer.concerns.acknowledge', $concern) }}">
                                @csrf @method('PATCH')
                                <button type="submit" class="dc-btn"><i class="bi bi-eye me-1"></i>Acknowledge</button>
                            </form>
                        @endif
                        <button type="button" class="dc-btn primary" data-bs-toggle="modal" data-bs-target="#closeConcernModal"
                                data-action="{{ route('developer.concerns.close', $concern) }}"
                                data-ref="{{ $concern->reference }}">
                            <i class="bi bi-check2-circle me-1"></i>Close
                        </button>
                    </div>
                @endif
            </div>
        @empty
            <div class="dc-empty"><i class="bi bi-inbox fs-3 d-block mb-2"></i>No {{ $tab }} concerns.</div>
        @endforelse
    </div>

    <div class="mt-3">{{ $concerns->onEachSide(1)->links('pagination::bootstrap-5') }}</div>
@endsection

@section('modals')
    <div class="modal fade" id="closeConcernModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <form method="POST" id="closeConcernForm" class="modal-content dc-form">
                @csrf @method('PATCH')
                <div class="modal-header">
                    <h5 class="modal-title">Close concern <span id="closeConcernRef"></span></h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <label for="closingNote" class="mb-1">Closing note (sent to the requester)</label>
                    <textarea name="closing_note" id="closingNote" class="form-control" rows="4" maxlength="5000" required
                              placeholder="What was done, or why it's being closed."></textarea>
                </div>
                <div class="modal-footer">
                    <button type="button" class="dc-btn" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="dc-btn primary"><i class="bi bi-check2-circle me-1"></i>Close concern</button>
                </div>
            </form>
        </div>
    </div>
@endsection

@section('scripts')
    <script>
        document.getElementById('closeConcernModal').addEventListener('show.bs.modal', function (e) {
            const btn = e.relatedTarget;
            document.getElementById('closeConcernForm').action = btn.getAttribute('data-action');
            document.getElementById('closeConcernRef').textContent = '#' + btn.getAttribute('data-ref');
            document.getElementById('closingNote').value = '';
        });
    </script>
@endsection
