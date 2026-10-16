<?php

namespace App\Http\Controllers\Api\Mobile;

use App\Http\Controllers\Controller;
use App\Http\Controllers\DeveloperConcernController as WebDeveloperConcernController;
use App\Models\DeveloperConcern;
use App\Support\Mobile\WebBridge;
use Illuminate\Http\Request;

// "Report to Developer" for the phone — ICT staff send concerns, the Developer
// acknowledges/closes them. Runs the web DeveloperConcernController (same
// validation, mails, statuses) through WebBridge.
class DeveloperConcernController extends Controller
{
    // Sender: my concerns, plus the category list for the form.
    public function index(Request $request)
    {
        $data = WebBridge::viewData($request, WebDeveloperConcernController::class, 'index');

        return response()->json([
            'data' => $data['concerns']->getCollection()->map(fn ($c) => $this->concern($c))->values(),
            'meta' => ['current_page' => $data['concerns']->currentPage(), 'last_page' => $data['concerns']->lastPage(), 'total' => $data['concerns']->total()],
            'categories' => collect(DeveloperConcern::CATEGORY_GROUPS)->map(fn ($items, $group) => [
                'group' => $group,
                'items' => collect($items)->map(fn ($hint, $name) => ['name' => $name, 'hint' => $hint])->values(),
            ])->values(),
            'urgencies' => DeveloperConcern::URGENCIES,
        ]);
    }

    public function store(Request $request)
    {
        $response = WebBridge::action($request, WebDeveloperConcernController::class, 'store', []);
        if ($response->getStatusCode() !== 200) {
            return $response;
        }

        $latest = DeveloperConcern::where('submitted_by', $request->user()->id)->latest()->first();

        return response()->json(['message' => $response->getData(true)['message'], 'concern' => $latest ? $this->concern($latest) : null], 201);
    }

    // Developer: the queue (?tab=open|acknowledged|closed) with counts.
    public function queue(Request $request)
    {
        $data = WebBridge::viewData($request, WebDeveloperConcernController::class, 'dashboard');

        return response()->json([
            'tab' => $data['tab'],
            'counts' => $data['counts'],
            'data' => $data['concerns']->getCollection()->map(fn ($c) => $this->concern($c))->values(),
            'meta' => ['current_page' => $data['concerns']->currentPage(), 'last_page' => $data['concerns']->lastPage(), 'total' => $data['concerns']->total()],
        ]);
    }

    public function acknowledge(Request $request, DeveloperConcern $concern)
    {
        return $this->act($request, 'acknowledge', $concern);
    }

    public function close(Request $request, DeveloperConcern $concern)
    {
        return $this->act($request, 'close', $concern);
    }

    private function act(Request $request, string $method, DeveloperConcern $concern)
    {
        $response = WebBridge::action($request, WebDeveloperConcernController::class, $method, ['concern' => $concern]);
        if ($response->getStatusCode() !== 200) {
            return $response;
        }

        return response()->json(['message' => $response->getData(true)['message'], 'concern' => $this->concern($concern->fresh('submitter.role'))]);
    }

    private function concern(DeveloperConcern $c): array
    {
        return [
            'id' => $c->id,
            'reference' => $c->reference,
            'subject' => $c->subject,
            'details' => $c->details,
            'category' => $c->category,
            'urgency' => $c->urgency,
            'status' => $c->status,
            'submitter' => $c->submitter ? ['name' => $c->submitter->name, 'role' => $c->submitter->role?->role_name] : null,
            'closing_note' => $c->closing_note,
            'acknowledged_at' => $c->acknowledged_at?->toIso8601String(),
            'closed_at' => $c->closed_at?->toIso8601String(),
            'created_at' => $c->created_at?->toIso8601String(),
        ];
    }
}
