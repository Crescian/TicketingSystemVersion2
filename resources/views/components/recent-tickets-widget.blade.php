{{-- Recent New Tickets — ICT-wide awareness widget. Shows the last 5 tickets
     submitted into the system, system-wide, with NO status/assignment/pending_role
     scoping — so the team sees a brand-new ticket land here even before it's been
     acknowledged, classified, or assigned to anyone. Complements (does not
     replace) each dashboard's own scoped queue tabs. --}}
@php
    $rtwTickets = \App\Models\Tickets::with('user')
        ->orderByDesc('created_at')
        ->limit(5)
        ->get();

    $rtwRoleName = \Illuminate\Support\Facades\Auth::user()->role?->role_name;
    $rtwShowRoute = match ($rtwRoleName) {
        'IT Admin' => 'admin.tickets.show',
        'Helpdesk' => 'helpdesk.tickets.show',
        'Supervisor - Support Specialist' => 'supervisor.support.tickets.show',
        default => null,
    };
@endphp

<div class="sidebar-card mb-3">
    <div style="background:var(--gd);color:var(--yg);padding:12px 18px;font-family:'Nunito',sans-serif;font-weight:800;font-size:12px;text-transform:uppercase;letter-spacing:.5px">
        <i class="bi bi-broadcast me-1"></i>Recent New Tickets
    </div>
    <div class="d-flex flex-column">
        @forelse($rtwTickets as $rtwTicket)
            @php
                $rtwIsNew = $rtwTicket->created_at->diffInMinutes(now()) < 60;
                $rtwRow = '
                    <div class="d-flex align-items-start gap-2 p-2" style="border-bottom:1px solid var(--bd)">
                        <span style="width:7px;height:7px;border-radius:50%;margin-top:5px;flex-shrink:0;background:' . ($rtwIsNew ? '#e24b4a' : 'var(--bd)') . '"></span>
                        <div style="min-width:0;flex:1">
                            <div style="font-size:11px;font-weight:800;color:var(--gd);display:flex;justify-content:space-between;gap:6px">
                                <span>#' . e($rtwTicket->ticket_number) . '</span>
                                <span style="color:var(--tm);font-weight:600;white-space:nowrap">' . e($rtwTicket->created_at->diffForHumans(null, true)) . ' ago</span>
                            </div>
                            <div style="font-size:12px;font-weight:700;color:var(--gd);white-space:nowrap;overflow:hidden;text-overflow:ellipsis;margin-top:2px">' . e($rtwTicket->subject) . '</div>
                            <div style="font-size:10px;color:var(--tm);font-weight:600;margin-top:2px">' . e($rtwTicket->user->name ?? 'Unknown') . ($rtwTicket->assigned_to ? '' : ' · <span style="color:#e24b4a">Unassigned</span>') . '</div>
                        </div>
                    </div>
                ';
            @endphp
            @if($rtwShowRoute)
                <a href="{{ route($rtwShowRoute, $rtwTicket) }}" style="text-decoration:none" class="rtw-row">{!! $rtwRow !!}</a>
            @else
                <div class="rtw-row">{!! $rtwRow !!}</div>
            @endif
        @empty
            <div class="p-3 text-center" style="font-size:12px;color:var(--tm)">No tickets yet.</div>
        @endforelse
    </div>
</div>

@once
<style>
    a.rtw-row:hover { background: var(--ygl); }
    .rtw-row:last-child > div { border-bottom: none !important; }
</style>
@endonce
