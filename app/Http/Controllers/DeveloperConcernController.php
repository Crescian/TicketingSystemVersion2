<?php

namespace App\Http\Controllers;

use App\Mail\DeveloperConcernAcknowledgedMail;
use App\Mail\DeveloperConcernClosedMail;
use App\Mail\DeveloperConcernSubmittedMail;
use App\Models\DeveloperConcern;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Mail;
use Illuminate\Validation\Rule;

// "Report to Developer": ICT staff send system concerns straight to the Developer
// role, who acknowledges and closes them. Sender routes are gated to
// DeveloperConcern::SENDER_ROLES, inbox routes to Developer (see routes/web.php).
class DeveloperConcernController extends Controller
{
    private const TABS = [
        'open' => DeveloperConcern::OPEN,
        'acknowledged' => DeveloperConcern::ACKNOWLEDGED,
        'closed' => DeveloperConcern::CLOSED,
    ];

    // ── Sender side

    public function index()
    {
        $concerns = DeveloperConcern::where('submitted_by', Auth::id())
            ->latest()
            ->paginate(15);

        return view('developer.concerns', [
            'concerns' => $concerns,
            'categoryGroups' => DeveloperConcern::CATEGORY_GROUPS,
            'urgencies' => DeveloperConcern::URGENCIES,
        ]);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'subject' => 'required|string|max:255',
            'details' => 'required|string|max:5000',
            'category' => ['required', Rule::in(DeveloperConcern::categories())],
            'urgency' => ['required', Rule::in(DeveloperConcern::URGENCIES)],
        ]);

        $concern = DeveloperConcern::create($data + ['submitted_by' => Auth::id()]);

        $developers = User::withActiveRole('Developer')->get();
        foreach ($developers as $developer) {
            Mail::to($developer->email)->send(new DeveloperConcernSubmittedMail($concern));
        }

        return redirect()->route('developer-concerns.index')
            ->with('success', "Concern #{$concern->reference} sent to the Developer.");
    }

    // ── Developer side

    public function dashboard(Request $request)
    {
        $tab = array_key_exists($request->query('tab'), self::TABS) ? $request->query('tab') : 'open';

        $concerns = DeveloperConcern::with('submitter.role')
            ->where('status', self::TABS[$tab])
            ->orderByRaw("CASE urgency WHEN 'High' THEN 1 WHEN 'Medium' THEN 2 ELSE 3 END")
            ->latest()
            ->paginate(15)
            ->withQueryString();

        $counts = collect(self::TABS)
            ->map(fn ($status) => DeveloperConcern::where('status', $status)->count());

        return view('developer.dashboard', compact('concerns', 'counts', 'tab'));
    }

    public function acknowledge(DeveloperConcern $concern)
    {
        if ($concern->status !== DeveloperConcern::OPEN) {
            return back()->with('error', 'This concern is not open.');
        }

        $concern->update([
            'status' => DeveloperConcern::ACKNOWLEDGED,
            'acknowledged_at' => now(),
        ]);

        $this->notifySubmitter($concern, new DeveloperConcernAcknowledgedMail($concern));

        return back()->with('success', "Concern #{$concern->reference} acknowledged.");
    }

    public function close(Request $request, DeveloperConcern $concern)
    {
        $request->validate([
            'closing_note' => 'required|string|max:5000',
        ]);

        if ($concern->status === DeveloperConcern::CLOSED) {
            return back()->with('error', 'This concern is already closed.');
        }

        $now = now();
        $concern->update([
            'status' => DeveloperConcern::CLOSED,
            'acknowledged_at' => $concern->acknowledged_at ?? $now,
            'closed_at' => $now,
            'closing_note' => $request->closing_note,
        ]);

        $this->notifySubmitter($concern, new DeveloperConcernClosedMail($concern));

        return back()->with('success', "Concern #{$concern->reference} closed.");
    }

    private function notifySubmitter(DeveloperConcern $concern, $mail): void
    {
        if ($concern->submitter?->email) {
            Mail::to($concern->submitter->email)->send($mail);
        }
    }
}
