<?php

namespace App\Http\Controllers\Api\Mobile;

use App\Exceptions\TicketActionException;
use App\Http\Controllers\Controller;
use App\Http\Controllers\TicketServiceReportController;
use App\Http\Resources\Mobile\TicketDetailResource;
use App\Http\Resources\Mobile\TicketResource;
use App\Models\TicketAttachment;
use App\Models\Tickets;
use App\Services\RequestorTicketService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

// The employee's own tickets for the mobile app — same rules as the web
// employee pages, via RequestorTicketService. Guard failures (e.g. cancelling
// after Helpdesk acknowledged) come back as 422 { message }.
class TicketController extends Controller
{
    private const ATTACHMENT_RULES = 'file|max:10240|mimes:jpg,jpeg,png,gif,pdf,doc,docx,xls,xlsx,txt';

    public function __construct(private RequestorTicketService $tickets)
    {
    }

    public function index(Request $request)
    {
        $request->validate([
            'status' => 'nullable|in:all,' . implode(',', array_keys(RequestorTicketService::PHASE_STATUSES)),
            'search' => 'nullable|string|max:100',
            'from_date' => 'nullable|date',
            'to_date' => 'nullable|date',
            'sort' => 'nullable|in:newest,oldest,priority',
            'per_page' => 'nullable|integer|min:1|max:50',
        ]);

        $tickets = $this->tickets
            ->listQuery($request->user(), $request->only(['status', 'search', 'from_date', 'to_date', 'sort']))
            ->with(['assignedTo', 'feedback', 'unreadMessages', 'slaCategory'])
            ->paginate((int) $request->input('per_page', 15));

        return TicketResource::collection($tickets);
    }

    public function counts(Request $request)
    {
        return response()->json($this->tickets->counts($request->user()));
    }

    public function show(Request $request, Tickets $ticket)
    {
        $this->tickets->assertOwner($ticket, $request->user());

        $ticket->load(['assignedTo', 'statusHistories.changedBy', 'feedback', 'attachments', 'slaCategory', 'unreadMessages']);

        return new TicketDetailResource($ticket);
    }

    public function store(Request $request)
    {
        $request->validate([
            'subject' => 'required|string|max:255',
            'concern' => 'required|string',
            'request_details' => 'nullable|string',
            'asset' => 'nullable|string|max:255',
            'location' => 'nullable|string|max:255',
            'attachments' => 'nullable|array|max:5',
            'attachments.*' => self::ATTACHMENT_RULES,
        ]);

        $ticket = $this->tickets->file(
            $this->tickets->selfFiledAttributes($request->user(), $request->all()),
            $request->file('attachments', []),
            $request->user(),
        );

        return $this->detail($ticket)->response()->setStatusCode(201);
    }

    public function update(Request $request, Tickets $ticket)
    {
        $request->validate([
            'subject' => 'required|string|max:255',
            'concern' => 'required|string',
            'request_details' => 'nullable|string',
            'location' => 'nullable|string|max:255',
            'attachments' => 'nullable|array|max:5',
            'attachments.*' => self::ATTACHMENT_RULES,
        ]);

        return $this->act(fn () => $this->tickets->update($ticket, $request->user(), $request->all(), $request->file('attachments', [])));
    }

    public function cancel(Request $request, Tickets $ticket)
    {
        return $this->act(fn () => $this->tickets->cancel($ticket, $request->user()));
    }

    // Confirm the resolution — closes the ticket.
    public function confirm(Request $request, Tickets $ticket)
    {
        return $this->act(fn () => $this->tickets->confirmResolution($ticket, $request->user()));
    }

    public function feedback(Request $request, Tickets $ticket)
    {
        $request->validate([
            'rating' => 'required|integer|min:1|max:5',
            'comments' => 'nullable|string|max:500',
        ]);

        return $this->act(function () use ($request, $ticket) {
            $this->tickets->rate($ticket, $request->user(), (int) $request->rating, $request->comments);

            return $ticket->unsetRelation('feedback');
        });
    }

    // A ticket's own files or its chat attachments — inline by default,
    // ?download=1 for a download. The requester, or any staff role — same
    // rule as the web (TicketsController::authorizeAttachmentAccess).
    public function attachment(Request $request, TicketAttachment $attachment)
    {
        $user = $request->user();
        $isStaff = !in_array($user->role?->role_name, ['Employee', 'Developer'], true);
        if (!$isStaff) {
            $this->tickets->assertOwner($attachment->ticket, $user);
        }

        if (!Storage::disk('local')->exists($attachment->stored_path)) {
            return response()->json(['message' => 'File no longer available.'], 404);
        }

        return $request->boolean('download')
            ? Storage::disk('local')->download($attachment->stored_path, $attachment->original_name)
            : Storage::disk('local')->response($attachment->stored_path, $attachment->original_name);
    }

    // Same PDF and the same owner/status rules as the web download.
    public function serviceReport(Tickets $ticket)
    {
        return app(TicketServiceReportController::class)->download($ticket);
    }

    private function act(callable $action)
    {
        try {
            return $this->detail($action());
        } catch (TicketActionException $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        }
    }

    private function detail(Tickets $ticket): TicketDetailResource
    {
        return new TicketDetailResource($ticket->fresh()->load([
            'assignedTo', 'statusHistories.changedBy', 'feedback', 'attachments', 'slaCategory', 'unreadMessages',
        ]));
    }
}
