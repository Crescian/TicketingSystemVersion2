@extends('layouts.app')

@section('title', 'Ticket #' . $ticket->ticket_number . ' — LGICT')

@section('nav-role-badge')
    <span class="role-badge"><i class="bi bi-person-check-fill me-1"></i>Supervisor</span>
@endsection
@section('avatar-initials',
    strtoupper(substr(Auth::user()->name, 0, 1)) .
    strtoupper(substr(Auth::user()->name, strpos(Auth::user()->name, ' ') + 1, 1))
)
@section('nav-username', Auth::user()->name)

@section('hero-title')
    <h1>SUPPORT REQUEST <em>DETAILS</em></h1>
@endsection
@section('hero-subtitle', 'Full history and status of this support request.')

@section('hero-stats')
    @php
        $badgeIcon = match($ticket->status) {
            'For Acknowledgment'          => '●',
            'Classified'                  => '◆',
            'Assigned'                    => '→',
            'In Progress Service Request' => '⟳',
            'Closed Service Request'      => '⟳',
            'In Progress Service Report'  => '⟳',
            'Done Service Report'         => '⏳',
            'Report For Review'           => '⏳',
            'Approved Service Report'     => '⏳',
            'Escalated'                   => '⚠',
            'Requestor Confirmation'      => '⏳',
            'Closed'                      => '✓',
            'Cancelled'                   => '✕',
            default                       => ''
        };
        $badgeClass = match($ticket->status) {
            'For Acknowledgment'          => 'badge-open',
            'Classified'                  => 'badge-open',
            'Assigned'                    => 'badge-in-progress',
            'In Progress Service Request' => 'badge-in-progress',
            'Closed Service Request'      => 'badge-in-progress',
            'In Progress Service Report'  => 'badge-in-progress',
            'Done Service Report'         => 'badge-awaiting-requestor',
            'Report For Review'           => 'badge-awaiting-requestor',
            'Approved Service Report'     => 'badge-awaiting-requestor',
            'Escalated'                   => 'badge-escalated',
            'Requestor Confirmation'      => 'badge-awaiting-requestor',
            'Closed'                      => 'badge-resolved',
            'Cancelled'                   => 'badge-cancelled',
            default                       => ''
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
    .btn-service-report { background:#e8f5ee; color:#1a5a3a; font-family:'Nunito',sans-serif; font-weight:800; font-size:13px; padding:8px 20px; border-radius:50px; border:1.5px solid #a8ddc0; transition:all .2s; cursor:pointer; text-decoration:none; display:inline-flex; align-items:center; gap:6px; }
    .btn-service-report:hover { background:#c8ead8; }
@endsection

@section('sidebar')
    {{-- Ticket quick info --}}
    <div class="sidebar-card mb-3">
        <div class="sidebar-head">Support Request Info</div>
        <div class="p-3 d-flex flex-column gap-3">
            <div>
                <div class="detail-lbl">Support Request Number</div>
                <div class="detail-val">#{{ $ticket->ticket_number }}</div>
            </div>
            <div>
                <div class="detail-lbl">Status</div>
                <span class="badge-status {{ $badgeClass }}">
                    {{ $badgeIcon }} {{ $ticket->status }}
                </span>
            </div>
            <div>
                <div class="detail-lbl">Requested By</div>
                <div style="font-size:13px;font-weight:600;color:var(--tm)">
                    {{ $ticket->user->name ?? '—' }}
                    @if($ticket->user?->department)
                        <br>{{ $ticket->user->department->name ?? '' }}
                    @endif
                </div>
            </div>
            <div>
                <div class="detail-lbl">Category</div>
                @if($ticket->subcategory_name)
                    <div class="detail-val">{{ $ticket->slaCategory->name ?? '—' }}</div>
                @else
                    <div style="font-size:13px;font-weight:600;color:var(--tm)">
                        Pending classification by Helpdesk
                    </div>
                @endif
            </div>
            @if($ticket->subcategory_name)
                <div>
                    <div class="detail-lbl">Subtask</div>
                    <div class="detail-val">{{ $ticket->subcategory_name }}</div>
                </div>
            @endif
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

    @if($ticket->status === 'Closed')
        <button type="button" class="btn-service-report w-100 justify-content-center mb-3"
                onclick="openServiceReportPreview('{{ $ticket->id }}', '{{ $ticket->ticket_number }}')">
            <i class="bi bi-file-earmark-pdf"></i> View Service Report
        </button>
    @endif

    {{-- Back button --}}
    <a href="{{ route('supervisor.support.dashboard') }}" class="btn-back-page w-100 justify-content-center">
        <i class="bi bi-arrow-left"></i> Back to Supervisor Dashboard
    </a>
@endsection

@section('content')

    @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show mb-3">
            <i class="bi bi-check-circle me-2"></i>{{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

    <x-ticket-tracker :ticket="$ticket" />

    {{-- Subject & concern --}}
    <div class="detail-card p-4 mb-3">
        <div class="ticket-id mb-2">#{{ $ticket->ticket_number }}</div>
        <div class="ticket-title mb-2" style="font-size:20px">{{ $ticket->subject }}</div>
        <div class="ticket-desc mb-4" style="-webkit-line-clamp:unset">{{ $ticket->concern }}</div>

        {{-- Classification & assignment --}}
        @if($ticket->subcategory_name)
            <div class="mb-4 p-3" style="background:var(--ygl);border-radius:12px;border:1px solid var(--gl)">
                <div class="detail-lbl mb-2">
                    <i class="bi bi-diagram-3 me-1"></i>Classified &amp; Assigned
                </div>
                <div class="d-flex flex-wrap gap-4">
                    <div>
                        <div class="detail-lbl mb-0">Category</div>
                        <div class="detail-val">{{ $ticket->slaCategory->name ?? '—' }}</div>
                    </div>
                    <div>
                        <div class="detail-lbl mb-0">Subtask</div>
                        <div class="detail-val">{{ $ticket->subcategory_name }}</div>
                    </div>
                    @if($ticket->assignedTo)
                        <div>
                            <div class="detail-lbl mb-0">ICT Support Specialist</div>
                            <div class="detail-val">{{ $ticket->assignedTo->name }}</div>
                        </div>
                    @endif
                </div>
            </div>
        @endif

        {{-- Additional details --}}
        @if($ticket->request_details)
            <div class="mb-4">
                <div class="detail-lbl mb-2">Additional Details</div>
                <div class="ticket-desc"
                     style="background:var(--ygl);padding:12px;border-radius:10px;-webkit-line-clamp:unset">
                    {{ $ticket->request_details }}
                </div>
            </div>
        @endif

        {{-- Supporting files --}}
        @if($ticket->attachments->isNotEmpty())
            <div class="mb-4">
                <div class="detail-lbl mb-2">Supporting Files</div>
                <div class="d-flex flex-column gap-2">
                    @foreach($ticket->attachments as $attachment)
                        <button type="button"
                           onclick="openAttachmentPreview('{{ $attachment->id }}', {{ Illuminate\Support\Js::from($attachment->original_name) }}, '{{ $attachment->mime_type }}')"
                           class="d-flex align-items-center gap-2 p-2 text-decoration-none border-0 text-start w-100"
                           style="background:var(--ygl);border-radius:10px;color:var(--gd);font-size:13px;font-weight:600">
                            <i class="bi bi-paperclip"></i>
                            {{ $attachment->original_name }}
                            <span style="color:var(--tm);font-weight:600;font-size:11px">({{ $attachment->humanSize() }})</span>
                            <i class="bi bi-eye ms-auto" style="color:var(--tm)"></i>
                        </button>
                    @endforeach
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
                    $dotClass = match (true) {
                        $history->new_status === 'Cancelled' => 'cancelled',
                        $history->new_status === 'Escalated' => 'escalated',
                        in_array($history->new_status, [
                            'Closed', 'Report For Review',
                            'Requestor Confirmation',
                        ], true) => 'resolved',
                        $history->new_status === 'For Acknowledgment' => 'open',
                        default => 'in-progress',
                    };
                @endphp
                <div class="tl-item">
                    <div class="tl-dot {{ $dotClass }}"></div>
                    <div class="tl-time">
                        {{ \Carbon\Carbon::parse($history->changed_at)->timezone('Asia/Manila')->format('M d, Y — g:i A') }}
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
    <x-service-report-modal />
    <x-attachment-preview-modal />
@endsection

@section('scripts')
{{-- Chat component --}}
<x-ticket-chat :ticket="$ticket" />
@endsection
