<?php

namespace App\Http\Controllers\Api\Mobile\Staff;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Dashboard\ExecutiveDashboardController;
use Illuminate\Http\Request;

// Executive dashboard ("Insights") for the phone — the web dashboard's own
// JSON endpoints, unchanged, except the IT team list is trimmed to the fields
// the app shows (the web sends whole user models). Manager, plus read-only
// for Helpdesk and both Supervisors, as on the web.
class ExecutiveController extends Controller
{
    public function data(Request $request)
    {
        $data = app()->call([app(ExecutiveDashboardController::class), 'data'])->getData(true);

        $data['itTeamStatus'] = collect($data['itTeamStatus'] ?? [])->map(fn ($m) => [
            'id' => $m['id'],
            'name' => $m['name'],
            'role' => $m['role']['role_name'] ?? null,
            'online' => (bool) ($m['online'] ?? false),
            'availability' => $m['availability'] ?? null,
            'active_tickets' => (int) ($m['active_tickets'] ?? 0),
        ])->values()->all();

        return response()->json($data);
    }

    public function feedback()
    {
        return app()->call([app(ExecutiveDashboardController::class), 'feedback']);
    }

    public function agingTickets()
    {
        return app()->call([app(ExecutiveDashboardController::class), 'agingTickets']);
    }

    public function resolutionTimeTop()
    {
        return app()->call([app(ExecutiveDashboardController::class), 'resolutionTimeTop']);
    }
}
