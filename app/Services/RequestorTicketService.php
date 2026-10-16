<?php

namespace App\Services;

use App\Exceptions\TicketActionException;
use App\Mail\TicketAssignedMail;
use App\Mail\TicketClosedByRequestorMail;
use App\Mail\TicketSubmittedMail;
use App\Models\TicketAttachment;
use App\Models\TicketFeedBack;
use App\Models\TicketStatusHistories;
use App\Models\Tickets;
use App\Models\User;
use App\Support\TicketStatus;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;

// Requestor-side ticket rules (file, edit, cancel, confirm resolution, rate, and
// the "My Requests" list filters) shared by the web employee pages
// (App\Http\Controllers\TicketsController) and the mobile API
// (App\Http\Controllers\Api\Mobile\TicketController), so the two can never
// disagree about what a requestor is allowed to do. Guard failures throw
// TicketActionException with the user-facing message; ownership failures abort(403).
class RequestorTicketService
{
    // Every employee self-filed ticket starts at this priority — the employee
    // form has no category/priority picker (that's Helpdesk/Supervisor's job at
    // classification), so nothing employee-supplied is trusted for it. Only
    // Helpdesk's own "file on behalf of" form (which has a real category+
    // subcategory picker wired to the SLA Rule table) can set it directly.
    public const DEFAULT_PRIORITY = 'Medium';

    // Roles the employee "Available IT" panel surfaces — the tiers a requester's
    // ticket can pass through (Helpdesk triage, the technician who gets assigned,
    // or an IT Admin/Supervisor track for admin-side requests) plus the
    // supervisors who oversee them. Same online-window mechanism as the
    // Admin/Executive presence panels (sessions.last_activity).
    private const IT_TEAM_ROLES = [
        'Helpdesk',
        'IT Support Specialist',
        'Supervisor - Support Specialist',
        'IT Admin',
        'Supervisor - IT Admin',
    ];

    private const ONLINE_WINDOW_MINUTES = 5;

    // Not-yet-started statuses a ticket passes through once a specialist is assigned —
    // matches App\Services\TicketScheduler's own NOT_STARTED_STATUSES, duplicated here
    // since that constant is private to the scheduler.
    private const QUEUED_STATUSES = [TicketStatus::ASSIGNED];

    // Phase filters — same 5-phase vocabulary as the simplified tracker on the
    // ticket card (App\Support\TicketTrackerStep: Submitted -> Scheduled -> In
    // Progress -> Awaiting Your Confirmation -> Closed), so a tab and the tracker
    // never disagree about where a ticket sits. The employee side doesn't surface
    // the detailed internal workflow (acknowledged vs. classified vs. assigned),
    // so those don't get separate tabs.
    public const PHASE_STATUSES = [
        'submitted' => [
            TicketStatus::FOR_ACKNOWLEDGMENT,
            TicketStatus::FOR_CLASSIFICATION,
            TicketStatus::CLASSIFIED,
        ],
        'scheduled' => [
            TicketStatus::ASSIGNED,
        ],
        'in_progress' => [
            TicketStatus::IN_PROGRESS_SERVICE_REQUEST, TicketStatus::CLOSED_SERVICE_REQUEST,
            TicketStatus::IN_PROGRESS_SERVICE_REPORT, TicketStatus::DONE_SERVICE_REPORT,
            TicketStatus::REPORT_FOR_REVIEW, TicketStatus::APPROVED_SERVICE_REPORT, TicketStatus::ESCALATED,
        ],
        'awaiting_requestor' => [
            TicketStatus::REQUESTOR_CONFIRMATION,
        ],
        'closed' => [
            TicketStatus::CLOSED,
        ],
        'cancelled' => [
            TicketStatus::CANCELLED,
        ],
    ];

    // ── List / counts ────────────────────────────────────────────────────────

    // The requestor's own tickets with the list filters applied — status (a
    // PHASE_STATUSES key or 'all'), search, category (classified SLA category),
    // from_date/to_date (submission date) and sort (newest|oldest|priority).
    public function listQuery(User $user, array $filters): Builder
    {
        $query = Tickets::where('users_id', $user->id);

        $status = $filters['status'] ?? 'all';
        if (isset(self::PHASE_STATUSES[$status])) {
            $query->whereIn('status', self::PHASE_STATUSES[$status]);
        }

        if ($search = $filters['search'] ?? null) {
            $query->where(function ($q) use ($search) {
                $q->where('subject', 'ilike', "%{$search}%")
                    ->orWhere('ticket_number', 'ilike', "%{$search}%")
                    ->orWhere('concern', 'ilike', "%{$search}%")
                    ->orWhere('request_category', 'ilike', "%{$search}%");
            });
        }

        // Category filter — matches the ticket's classified SLA category (only
        // set once Helpdesk classifies it), not the employee's self-submitted
        // request_category, which isn't reliably populated.
        if (!empty($filters['category'])) {
            $query->where('sla_category_id', $filters['category']);
        }

        if (!empty($filters['from_date'])) {
            $query->whereDate('created_at', '>=', $filters['from_date']);
        }
        if (!empty($filters['to_date'])) {
            $query->whereDate('created_at', '<=', $filters['to_date']);
        }

        match ($filters['sort'] ?? 'newest') {
            'oldest' => $query->oldest(),
            'priority' => $query->orderByRaw("CASE ticket_type WHEN 'Critical' THEN 1 WHEN 'High' THEN 2 WHEN 'Medium' THEN 3 WHEN 'Low' THEN 4 END"),
            default => $query->latest(),
        };

        return $query;
    }

    // Per-tab counts: 'all' plus one per PHASE_STATUSES key.
    public function counts(User $user): array
    {
        $byStatus = Tickets::where('users_id', $user->id)
            ->selectRaw('status, count(*) as total')
            ->groupBy('status')
            ->pluck('total', 'status');

        $counts = ['all' => (int) $byStatus->sum()];
        foreach (self::PHASE_STATUSES as $key => $statuses) {
            $counts[$key] = (int) $byStatus->only($statuses)->sum();
        }

        return $counts;
    }

    // A soft, employee-facing ETA for when a specialist will actually start on this
    // ticket — deliberately fuzzy (rounded hour today, day name if further out) rather
    // than the exact scheduled_start minute, since that projection can legitimately
    // shift later (a higher-priority ticket jumping the queue) or earlier (an earlier
    // ticket finishing ahead of schedule). Showing a precise time that then moves would
    // read as a broken promise even though the system behaved correctly.
    public function expectedStartLabel(Tickets $ticket): ?string
    {
        if (in_array($ticket->status, [TicketStatus::FOR_ACKNOWLEDGMENT, TicketStatus::FOR_CLASSIFICATION, TicketStatus::CLASSIFIED], true)) {
            return 'Awaiting assignment';
        }

        if ($ticket->status === TicketStatus::IN_PROGRESS_SERVICE_REQUEST) {
            return 'Being worked on now';
        }

        if (!in_array($ticket->status, self::QUEUED_STATUSES, true) || !$ticket->scheduled_start) {
            return null;
        }

        $now = now('Asia/Manila');
        $start = $ticket->scheduled_start->copy()->timezone('Asia/Manila');

        if ($start->isSameDay($now)) {
            return $start->lte($now) ? 'Today, shortly' : 'Today, after ' . $start->format('g:00 A');
        }

        if ($start->isSameDay($now->copy()->addDay())) {
            return 'Tomorrow';
        }

        if ($now->diffInDays($start) <= 6) {
            return $start->format('l');
        }

        return 'On ' . $start->format('M j');
    }

    // Active IT team members with an `online` flag, online first.
    public function itTeamStatus(): Collection
    {
        $onlineIds = \App\Support\Presence::onlineUserIds(self::ONLINE_WINDOW_MINUTES);

        return User::with('role')
            ->whereHas('role', fn ($q) => $q->whereIn('role_name', self::IT_TEAM_ROLES))
            ->where('active', true)
            ->orderBy('name')
            ->get()
            ->map(function ($member) use ($onlineIds) {
                $member->online = $onlineIds->contains($member->id);
                return $member;
            })
            ->sortByDesc('online')
            ->values();
    }

    // ── Filing ───────────────────────────────────────────────────────────────

    // Ticket attributes for a requestor filing for themselves — requestor
    // context, date/time received and method are auto-filled, never trusted
    // from input. department/company/business_unit are traversed off
    // User::department() (a belongsTo relation, not a plain column).
    public function selfFiledAttributes(User $requestor, array $input): array
    {
        return [
            'users_id'         => $requestor->id,
            'ticket_type'      => self::DEFAULT_PRIORITY,
            'request_category' => null,
            'subject'          => $input['subject'],
            'concern'          => $input['concern'],
            'request_details'  => $input['request_details'] ?? null,
            'asset'            => $input['asset'] ?? null,
            'location'         => $input['location'] ?? null,
            'position'         => $requestor->position ?? null,
            'business_unit'    => $requestor->department?->company?->businessUnit?->business_units_name ?? null,
            'company'          => $requestor->department?->company?->company_name ?? null,
            'department'       => $requestor->department?->department_name ?? null,
            // Plain strings, not Carbon-cast — Asia/Manila wall-clock values,
            // since app.timezone is UTC.
            'date_received'    => now()->timezone('Asia/Manila')->toDateString(),
            'time_received'    => now()->timezone('Asia/Manila')->format('H:i'),
            'method'           => 'System',
        ];
    }

    // Creates the ticket in Helpdesk's acknowledgment queue, stores its
    // attachments, writes the first history row, emails the requestor and —
    // for self-filed tickets — notifies Helpdesk (Helpdesk owns triage of
    // tickets it filed itself, so it doesn't need telling about those).
    public function file(array $attributes, array $files, User $filer, bool $filedByHelpdesk = false): Tickets
    {
        $ticket = Tickets::create($attributes + [
            'ticket_number'    => Tickets::generateTicketNumber(),
            'status'           => TicketStatus::FOR_ACKNOWLEDGMENT,
            'pending_role'     => TicketStatus::QUEUE_HELPDESK,
            'escalation_level' => 0,
        ]);

        $this->storeAttachments($ticket, $files, $filer);

        TicketStatusHistories::create([
            'ticket_id'  => $ticket->id,
            'old_status' => null,
            'new_status' => TicketStatus::FOR_ACKNOWLEDGMENT,
            'changed_by' => $filer->id,
            'notes'      => $filedByHelpdesk
                ? 'Ticket filed by Helpdesk on behalf of employee.'
                : 'Ticket submitted by employee.',
            'changed_at' => now(),
        ]);

        $requestor = $ticket->user;
        if ($requestor && $requestor->email) {
            Mail::to($requestor->email)->send(new TicketSubmittedMail($ticket));
        }

        if (!$filedByHelpdesk) {
            $this->notifyHelpdesk($ticket, 'A new ticket has been submitted and needs triage.');
        }

        return $ticket;
    }

    /** @param  UploadedFile[]  $files */
    public function storeAttachments(Tickets $ticket, array $files, User $uploader, ?string $messageId = null): Collection
    {
        return collect($files)->map(function (UploadedFile $file) use ($ticket, $uploader, $messageId) {
            $storedPath = $file->store('ticket-attachments/' . $ticket->id, 'local');

            return TicketAttachment::create([
                'ticket_id'         => $ticket->id,
                'ticket_message_id' => $messageId,
                'uploaded_by'       => $uploader->id,
                'original_name'     => $file->getClientOriginalName(),
                'stored_path'       => $storedPath,
                'mime_type'         => $file->getClientMimeType(),
                'size'              => $file->getSize(),
            ]);
        });
    }

    // ── Requestor actions ────────────────────────────────────────────────────

    public function assertOwner(Tickets $ticket, User $user): void
    {
        if ($ticket->users_id !== $user->id) {
            abort(403);
        }
    }

    // Edit/cancel are only possible while the ticket is still sitting
    // unacknowledged in Helpdesk's queue.
    public function isStillWithHelpdesk(Tickets $ticket): bool
    {
        return $ticket->status === TicketStatus::FOR_ACKNOWLEDGMENT
            && $ticket->pending_role === TicketStatus::QUEUE_HELPDESK
            && is_null($ticket->date_acknowledged);
    }

    public function update(Tickets $ticket, User $user, array $input, array $files): Tickets
    {
        $this->assertOwner($ticket, $user);

        if (!$this->isStillWithHelpdesk($ticket)) {
            throw new TicketActionException('This support request can no longer be edited — it has already been acknowledged by Helpdesk.');
        }

        $ticket->update([
            'subject'         => $input['subject'],
            'concern'         => $input['concern'],
            'request_details' => $input['request_details'] ?? null,
            'location'        => $input['location'] ?? null,
        ]);

        $this->storeAttachments($ticket, $files, $user);

        TicketStatusHistories::create([
            'ticket_id'  => $ticket->id,
            'old_status' => $ticket->status,
            'new_status' => $ticket->status,
            'changed_by' => $user->id,
            'notes'      => 'Request details updated by employee.',
            'changed_at' => now(),
        ]);

        // Same pattern as a fresh submission — Helpdesk hasn't acknowledged this
        // ticket yet, so they need to know the details changed before they do.
        $this->notifyHelpdesk($ticket, 'The employee updated this request\'s details before it was acknowledged.');

        return $ticket;
    }

    public function cancel(Tickets $ticket, User $user): Tickets
    {
        $this->assertOwner($ticket, $user);

        if (!$this->isStillWithHelpdesk($ticket)) {
            throw new TicketActionException('This ticket can no longer be cancelled — it has already been acknowledged by Helpdesk.');
        }

        $oldStatus = $ticket->status;

        $ticket->update(['status' => TicketStatus::CANCELLED]);

        TicketStatusHistories::create([
            'ticket_id'  => $ticket->id,
            'old_status' => $oldStatus,
            'new_status' => TicketStatus::CANCELLED,
            'changed_by' => $user->id,
            'notes'      => 'Cancelled by employee.',
            'changed_at' => now(),
        ]);

        return $ticket;
    }

    // Requestor confirms the resolution -> ticket moves to Closed.
    public function confirmResolution(Tickets $ticket, User $user): Tickets
    {
        $this->assertOwner($ticket, $user);

        if ($ticket->status !== TicketStatus::REQUESTOR_CONFIRMATION) {
            throw new TicketActionException('This ticket is not currently awaiting your acknowledgment.');
        }

        $oldStatus = $ticket->status;
        $now = now();

        $ticket->update([
            'status'      => TicketStatus::CLOSED,
            'resolved_at' => $ticket->resolved_at ?? $now,
            'closed_at'   => $now,
        ]);

        TicketStatusHistories::create([
            'ticket_id'  => $ticket->id,
            'old_status' => $oldStatus,
            'new_status' => TicketStatus::CLOSED,
            'changed_by' => $user->id,
            'notes'      => 'Requestor acknowledged resolution — ticket closed.',
            'changed_at' => $now,
        ]);

        // FYI to Helpdesk (coordination hub, same convention as
        // notifyHelpdeskFyi() in SupervisorDashboardController) plus whoever
        // actually resolved the ticket — they're the internal/ICT side that
        // cares this closed the loop. Deduped by id in case the resolver is
        // themselves a Helpdesk agent.
        $recipients = User::withActiveRole('Helpdesk')->get()
            ->when($ticket->resolvedBy, fn ($users) => $users->push($ticket->resolvedBy))
            ->unique('id');

        foreach ($recipients as $recipient) {
            if ($recipient->email) {
                Mail::to($recipient->email)->send(new TicketClosedByRequestorMail($ticket));
            }
        }

        return $ticket;
    }

    // Feedback is only for Closed tickets, once per ticket.
    public function assertCanRate(Tickets $ticket, User $user): void
    {
        $this->assertOwner($ticket, $user);

        if ($ticket->status !== TicketStatus::CLOSED) {
            throw new TicketActionException('You can only rate closed tickets.');
        }

        if ($ticket->feedback) {
            throw new TicketActionException('You have already submitted feedback for this ticket.');
        }
    }

    public function rate(Tickets $ticket, User $user, int $rating, ?string $comments): TicketFeedBack
    {
        $this->assertCanRate($ticket, $user);

        return TicketFeedBack::create([
            'ticket_id'  => $ticket->id,
            'user_id'    => $user->id,
            'rating'     => $rating,
            'comments'   => $comments,
            'created_at' => now(),
        ]);
    }

    private function notifyHelpdesk(Tickets $ticket, string $message): void
    {
        foreach (User::withActiveRole('Helpdesk')->get() as $helpdeskUser) {
            Mail::to($helpdeskUser->email)->send(
                new TicketAssignedMail($ticket, $message, 'helpdesk.dashboard')
            );
        }
    }
}
