@extends('layouts.app')

@section('title', 'Ticket #' . $ticket->ticket_number . ' — LGICT')

@section('nav-role-badge')
@endsection
@section('avatar-initials',
    strtoupper(substr(Auth::user()->name, 0, 1)) .
    strtoupper(substr(Auth::user()->name, strpos(Auth::user()->name, ' ') + 1, 1))
)
@section('nav-username', explode(' ', Auth::user()->name)[0] . ' ' . strtoupper(substr(explode(' ', Auth::user()->name)[1] ?? '', 0, 1)) . '.')

@section('hero-title')
    <h1>TICKET <em>DETAILS</em></h1>
@endsection
@section('hero-subtitle', 'Full history and status of your support request.')

@section('hero-stats')
    @php
        $badgeIcon = match($ticket->status) {
            'Open'        => '●',
            'In Progress' => '⟳',
            'Escalated'   => '⚠',
            'Resolved'    => '✓',
            'Cancelled'   => '✕',
            default       => ''
        };
        $badgeClass = match($ticket->status) {
            'Open'        => 'badge-open',
            'In Progress' => 'badge-in-progress',
            'Escalated'   => 'badge-escalated',
            'Resolved'    => 'badge-resolved',
            'Cancelled'   => 'badge-cancelled',
            default       => ''
        };
    @endphp
    <div class="d-flex gap-2 flex-wrap align-items-center">
        <span class="badge-status {{ $badgeClass }}" style="font-size:14px;padding:8px 16px">
            {{ $badgeIcon }} {{ $ticket->status }}
        </span>
        <span class="ticket-id" style="font-size:16px">#{{ $ticket->ticket_number }}</span>
    </div>
@endsection

@section('styles')
    .detail-card { background: var(--cr); border: 1.5px solid var(--bd); border-radius: 16px; }
    .detail-lbl { font-size: 11px; font-weight: 700; color: var(--tm); text-transform: uppercase; letter-spacing: .4px; margin-bottom: 4px; }
    .detail-val { font-size: 14px; font-weight: 700; color: var(--gd); }
    .timeline-wrap { position: relative; padding-left: 24px; }
    .timeline-wrap::before { content: ''; position: absolute; left: 7px; top: 8px; bottom: 8px; width: 2px; background: var(--bd); }
    .tl-item { position: relative; margin-bottom: 20px; }
    .tl-dot { position: absolute; left: -20px; top: 4px; width: 14px; height: 14px; border-radius: 50%; background: var(--yg); border: 2px solid var(--gd); flex-shrink: 0; }
    .tl-dot.open        { background: #888; border-color: #888; }
    .tl-dot.in-progress { background: var(--yg); border-color: var(--gd); }
    .tl-dot.escalated   { background: #e24b4a; border-color: #e24b4a; }
    .tl-dot.resolved    { background: #4a7c4a; border-color: #4a7c4a; }
    .tl-dot.cancelled   { background: #555; border-color: #555; }
    .tl-time { font-size: 11px; color: var(--tm); font-weight: 600; }
    .tl-title { font-size: 14px; font-weight: 800; color: var(--gd); margin: 2px 0; }
    .tl-desc  { font-size: 13px; color: var(--tm); }
    .btn-back-page { background: none; border: 1.5px solid var(--bd); color: var(--tm); font-family: 'Nunito', sans-serif; font-weight: 800; font-size: 13px; padding: 8px 20px; border-radius: 50px; transition: all .2s; text-decoration: none; display: inline-flex; align-items: center; gap: 6px; }
    .btn-back-page:hover { border-color: var(--gl); color: var(--gd); }
    .btn-cancel-ticket { background: none; border: 1.5px solid #e24b4a; color: #e24b4a; font-family: 'Nunito', sans-serif; font-weight: 800; font-size: 13px; padding: 8px 20px; border-radius: 50px; transition: all .2s; cursor: pointer; }
    .btn-cancel-ticket:hover { background: #e24b4a; color: #fff; }
@endsection

@section('sidebar')
    {{-- Ticket quick info --}}
    <div class="sidebar-card mb-3">
        <div class="sidebar-head">Ticket Info</div>
        <div class="p-3 d-flex flex-column gap-3">
            <div>
                <div class="detail-lbl">Ticket Number</div>
                <div class="detail-val">#{{ $ticket->ticket_number }}</div>
            </div>
            <div>
                <div class="detail-lbl">Status</div>
                <span class="badge-status {{ $badgeClass }}">
                    {{ $badgeIcon }} {{ $ticket->status }}
                </span>
            </div>
            <div>
                <div class="detail-lbl">Priority</div>
                @php
                    $priColor = match($ticket->ticket_type) {
                        'High'   => '#e24b4a',
                        'Medium' => '#f5c842',
                        'Low'    => '#4a7c4a',
                        default  => 'var(--tm)'
                    };
                @endphp
                <div class="detail-val" style="color:{{ $priColor }}">
                    {{ $ticket->ticket_type }}
                </div>
            </div>
            <div>
                <div class="detail-lbl">Category</div>
                <div class="detail-val">{{ $ticket->request_category }}</div>
            </div>
            <div>
                <div class="detail-lbl">Submitted</div>
                <div style="font-size:13px;font-weight:600;color:var(--tm)">
                    {{ $ticket->created_at->format('M d, Y') }}<br>
                    {{ $ticket->created_at->format('g:i A') }}
                </div>
            </div>
            @if($ticket->asset)
                <div>
                    <div class="detail-lbl">Asset</div>
                    <div style="font-size:13px;font-weight:600;color:var(--tm)">
                        {{ $ticket->asset }}
                    </div>
                </div>
            @endif
            @if($ticket->location)
                <div>
                    <div class="detail-lbl">Location</div>
                    <div style="font-size:13px;font-weight:600;color:var(--tm)">
                        {{ $ticket->location }}
                    </div>
                </div>
            @endif
            <div>
                <div class="detail-lbl">Assigned To</div>
                @if($ticket->assignedTo)
                    @php
                        $initials = strtoupper(substr($ticket->assignedTo->name, 0, 1)) .
                            strtoupper(substr($ticket->assignedTo->name, strpos($ticket->assignedTo->name, ' ') + 1, 1));
                    @endphp
                    <span class="tech-chip">
                        <span class="tc-av">{{ $initials }}</span>
                        {{ $ticket->assignedTo->name }}
                    </span>
                @else
                    <span class="tech-chip">
                        <span class="tc-av" style="background:#888">—</span>
                        Unassigned
                    </span>
                @endif
            </div>
            @if($ticket->resolved_at)
                <div>
                    <div class="detail-lbl">Resolved At</div>
                    <div style="font-size:13px;font-weight:600;color:#4a7c4a">
                        {{ $ticket->resolved_at->format('M d, Y — g:i A') }}
                    </div>
                </div>
            @endif
        </div>
    </div>

    {{-- Back button --}}
    <a href="{{ route('employee.tickets.index') }}" class="btn-back-page w-100 justify-content-center">
        <i class="bi bi-arrow-left"></i> Back to My Tickets
    </a>
@endsection

@section('content')

    @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show mb-3">
            <i class="bi bi-check-circle me-2"></i>{{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

    {{-- Subject & concern --}}
    <div class="detail-card p-4 mb-3">
        <div class="ticket-id mb-2">#{{ $ticket->ticket_number }}</div>
        <div class="ticket-title mb-2" style="font-size:20px">{{ $ticket->subject }}</div>
        <div class="ticket-desc mb-4">{{ $ticket->concern }}</div>

        {{-- Additional details --}}
        @if($ticket->request_details)
            <div class="mb-4">
                <div class="detail-lbl mb-2">Additional Details</div>
                <div class="ticket-desc"
                     style="background:var(--ygl);padding:12px;border-radius:10px">
                    {{ $ticket->request_details }}
                </div>
            </div>
        @endif

        {{-- Escalation notice --}}
        @if($ticket->status === 'Escalated')
            <div class="esc-banner p-2 mb-3">
                <i class="bi bi-exclamation-triangle-fill me-1"></i>
                This ticket has been escalated to IT Admin — Level {{ $ticket->escalation_level }}.
                @if($ticket->assignedTo)
                    Currently handled by {{ $ticket->assignedTo->name }}.
                @endif
            </div>
        @endif

        {{-- Cancel button --}}
        @if(in_array($ticket->status, ['Open', 'In Progress']))
            <button class="btn-cancel-ticket"
                    onclick="confirmCancel('{{ $ticket->id }}', '{{ $ticket->ticket_number }}')">
                <i class="bi bi-x-circle me-1"></i>Cancel This Ticket
            </button>
        @endif
    </div>

    {{-- Status History Timeline --}}
    <div class="detail-card p-4">
        <div class="font-brand fw-900 mb-4"
             style="font-size:14px;text-transform:uppercase;letter-spacing:.5px;color:var(--gd)">
            <i class="bi bi-clock-history me-2"></i>Status History
        </div>

        <div class="timeline-wrap">
            @forelse($ticket->statusHistories->sortBy('changed_at') as $history)
                @php
                    $dotClass = match($history->new_status) {
                        'Open'        => 'open',
                        'In Progress' => 'in-progress',
                        'Escalated'   => 'escalated',
                        'Resolved'    => 'resolved',
                        'Cancelled'   => 'cancelled',
                        default       => ''
                    };
                @endphp
                <div class="tl-item">
                    <div class="tl-dot {{ $dotClass }}"></div>
                    <div class="tl-time">
                        {{ \Carbon\Carbon::parse($history->changed_at)->format('M d, Y — g:i A') }}
                        · {{ \Carbon\Carbon::parse($history->changed_at)->diffForHumans() }}
                    </div>
                    <div class="tl-title">
                        Status changed to <em>{{ $history->new_status }}</em>
                        @if($history->changedBy)
                            by {{ $history->changedBy->name }}
                        @endif
                    </div>
                    @if($history->notes)
                        <div class="tl-desc">{{ $history->notes }}</div>
                    @endif
                </div>
            @empty
                <div style="color:var(--tm);font-size:13px">
                    No history available yet.
                </div>
            @endforelse
        </div>
    </div>

@endsection

@section('modals')
    {{-- Cancel Confirmation Modal --}}
    <div class="modal fade" id="cancelModal" tabindex="-1">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header-gd d-flex align-items-center justify-content-between">
                    <h5 class="mb-0">Cancel <em>Ticket</em></h5>
                    <button class="btn-close-w" data-bs-dismiss="modal">✕</button>
                </div>
                <div class="modal-body px-4 py-4">
                    <div class="p-3 rounded"
                         style="background:rgba(226,75,74,.1);border:1px solid rgba(226,75,74,.3);color:#e24b4a;font-size:13px;font-weight:600">
                        <i class="bi bi-exclamation-triangle me-1"></i>
                        Are you sure you want to cancel ticket
                        <strong id="cancelTicketRef"></strong>?
                        This cannot be undone.
                    </div>
                </div>
                <div class="modal-footer border-top px-4 py-3 d-flex justify-content-between">
                    <button class="btn-back-modal" data-bs-dismiss="modal">Go Back</button>
                    <form id="cancelForm" method="POST">
                        @csrf
                        @method('PATCH')
                        <button type="submit" class="btn-cancel-ticket"
                                style="padding:10px 24px">
                            <i class="bi bi-x-circle me-1"></i>Yes, Cancel Ticket
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>
@endsection

@section('scripts')
<script>
window.confirmCancel = function (ticketId, ticketNumber) {
    $('#cancelTicketRef').text('#' + ticketNumber);
    $('#cancelForm').attr('action', '{{ url("employee/tickets") }}/' + ticketId + '/cancel');
    new bootstrap.Modal('#cancelModal').show();
};
</script>
@endsection