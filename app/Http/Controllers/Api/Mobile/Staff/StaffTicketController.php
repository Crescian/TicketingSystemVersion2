<?php

namespace App\Http\Controllers\Api\Mobile\Staff;

use App\Http\Controllers\Controller;
use App\Http\Resources\Mobile\Staff\StaffTicketDetailResource;
use App\Http\Resources\Mobile\Staff\StaffTicketResource;
use App\Models\Tickets;
use App\Models\User;
use App\Support\Mobile\StaffActions;
use App\Support\Mobile\StaffQueues;
use App\Support\Mobile\WebBridge;
use App\Support\TicketStatus;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

// Staff ticket work on the phone: each role's queue, the pick-lists its forms
// need, one ticket's full detail with the actions available now, and running
// an action — all through the role's own web dashboard/controller (WebBridge),
// so the rules are the web's.
class StaffTicketController extends Controller
{
    // Roles that can open any ticket (their web dashboards list every track).
    private const SEE_ALL_ROLES = ['Helpdesk', 'Supervisor - Support Specialist', 'Supervisor - IT Admin', 'Manager'];

    public function queue(Request $request)
    {
        $queue = $this->queueFor($request->user());
        $request->validate([
            'status' => 'nullable|string|in:' . implode(',', array_keys($queue['tabs'])),
            'search' => 'nullable|string|max:100',
            'sort' => 'nullable|in:newest,oldest,priority',
        ]);
        if (!$request->filled('status')) {
            $request->merge(['status' => $queue['defaultTab']]);
        }

        $data = WebBridge::viewData($request, $queue['controller'], $queue['method']);
        $tickets = $data['tickets'];
        $tickets->getCollection()->load(['slaCategory', 'unreadMessages']);

        return response()->json([
            'tab' => $request->status,
            'tabs' => StaffQueues::tabs($queue, $data['counts'] ?? []),
            'data' => StaffTicketResource::collection($tickets->getCollection())->resolve($request),
            'meta' => [
                'current_page' => $tickets->currentPage(),
                'last_page' => $tickets->lastPage(),
                'total' => $tickets->total(),
            ],
            // Specialists/IT Admins: new tickets they may take while Helpdesk is away.
            'self_triage' => isset($data['selfTriageQueue']) && $data['selfTriageQueue']->isNotEmpty()
                ? StaffTicketResource::collection($data['selfTriageQueue'])->resolve($request)
                : [],
        ]);
    }

    // Pick-lists for action forms (SLA categories/rules, workload classes,
    // assignees with their current capacity) — the same lists the role's web
    // dashboard passes to its modals.
    public function lookups(Request $request)
    {
        $queue = $this->queueFor($request->user());
        $request->merge(['status' => $queue['defaultTab']]);
        $data = WebBridge::viewData($request, $queue['controller'], $queue['method']);

        return response()->json([
            'sla_rules' => self::categories($data['slaCategoriesJson'] ?? []),
            'self_triage_rules' => self::categories($data['selfTriageCategoriesJson'] ?? []),
            'workload_classes' => collect(self::decode($data['workloadClassesJson'] ?? []))->map(fn ($w) => [
                'id' => $w['id'],
                'name' => $w['name'],
                'hint' => trim(($w['response_minutes'] ?? '?') . ' min response · ' . ($w['resolution_minutes'] ?? '?') . ' min resolution'),
            ])->values(),
            'assignees' => $queue['assignees'] ? collect($data[$queue['assignees']] ?? [])->map(fn (User $u) => [
                'id' => $u->id,
                'name' => $u->name,
                'availability' => $u->availability ?? null,
                'hint' => collect([
                    match ($u->availability ?? null) { 'free' => 'Available', 'busy' => 'Busy', 'full' => 'Full', default => null },
                    $u->free_time_label ?? null,
                    isset($u->in_progress_count) ? "{$u->in_progress_count} in progress" : (isset($u->active_tickets) ? "{$u->active_tickets} active" : null),
                ])->filter()->implode(' · '),
            ])->values() : [],
        ]);
    }

    public function show(Request $request, Tickets $ticket)
    {
        $this->authorizeView($request->user(), $ticket);

        return new StaffTicketDetailResource($ticket->load(StaffTicketDetailResource::RELATIONS));
    }

    public function act(Request $request, Tickets $ticket, string $action): JsonResponse
    {
        $user = $request->user();
        $this->authorizeView($user, $ticket);

        $definition = StaffActions::find($user->role?->role_name, $action);
        abort_if(!$definition, 404, 'Unknown action.');

        // Only what the web would offer for this ticket now; the controller
        // re-checks its own guards anyway.
        if (!($definition['when'])($ticket->load('assignedTo.role'), $user)) {
            return response()->json(['message' => "This action isn't available for this ticket right now. Pull to refresh."], 422);
        }

        $response = WebBridge::action($request, $definition['controller'], $definition['method'], ['ticket' => $ticket]);

        if ($response->getStatusCode() !== 200) {
            return $response;
        }

        $fresh = Tickets::find($ticket->id)->load(StaffTicketDetailResource::RELATIONS);

        return response()->json([
            'message' => $response->getData(true)['message'] ?? 'Done.',
            'ticket' => (new StaffTicketDetailResource($fresh))->resolve($request),
        ]);
    }

    private function queueFor(User $user): array
    {
        $queue = StaffQueues::for($user->role?->role_name);
        abort_if(!$queue, 403, 'Your role has no ticket queue.');

        return $queue;
    }

    // Specialists and IT Admins only see their own work (as on the web): tickets
    // assigned to them, ones they escalated or filed, and new tickets they may
    // self-triage while Helpdesk is away.
    private function authorizeView(User $user, Tickets $ticket): void
    {
        $role = $user->role?->role_name;
        if (in_array($role, self::SEE_ALL_ROLES, true)) {
            return;
        }

        $allowed = in_array($role, ['IT Support Specialist', 'IT Admin'], true) && (
            $ticket->assigned_to === $user->id
            || $ticket->users_id === $user->id
            || $ticket->escalations()->where('escalated_by', $user->id)->exists()
            || ($ticket->status === TicketStatus::FOR_ACKNOWLEDGMENT && $ticket->pending_role === TicketStatus::QUEUE_HELPDESK && !$ticket->date_acknowledged)
        );

        abort_if(!$allowed, 403, 'You do not have access to this ticket.');
    }

    // Normalise the dashboards' SLA category JSON (a few slightly different
    // shapes) to [{id, name, rules: [{id, name, priority, hint}]}].
    private static function categories(mixed $json): array
    {
        return collect(self::decode($json))->map(fn ($c) => [
            'id' => $c['id'],
            'name' => $c['name'],
            'rules' => collect($c['subs'] ?? [])->map(fn ($r) => [
                'id' => $r['rule_id'],
                'name' => $r['name'],
                'priority' => $r['priority'] ?? null,
                'hint' => collect([
                    $r['priority'] ?? null,
                    isset($r['response']) ? "{$r['response']} min response" : null,
                    isset($r['resolution']) ? "{$r['resolution']} min resolution" : null,
                ])->filter()->implode(' · '),
            ])->values(),
        ])->filter(fn ($c) => $c['rules']->isNotEmpty())->values()->all();
    }

    private static function decode(mixed $value): array
    {
        if (is_string($value)) {
            return json_decode($value, true) ?: [];
        }

        return collect($value)->toArray();
    }
}
