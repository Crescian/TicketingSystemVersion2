<?php

namespace App\Http\Controllers\Api\Mobile\Staff;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Dashboard\WorkHoursCalendarController;
use App\Support\Mobile\WebBridge;
use Carbon\Carbon;
use Illuminate\Http\Request;

// Work-hours calendar for the phone — the web calendar's own data
// (WorkHoursCalendarController, run through WebBridge): specialists/IT admins
// see themselves, supervisors their track. ?view=day|week|month&date=Y-m-d
// [&technician_id=] (supervisors only).
class StaffCalendarController extends Controller
{
    public function show(Request $request)
    {
        $request->validate([
            'view' => 'nullable|in:day,week,month',
            'date' => 'nullable|date_format:Y-m-d',
            'technician_id' => 'nullable|uuid',
        ]);

        $data = WebBridge::viewData($request, WorkHoursCalendarController::class, 'index');
        $person = fn ($u) => ['id' => $u->id, 'name' => $u->name];

        $base = [
            'view' => $data['view'],
            'date' => $data['date']->format('Y-m-d'),
            'nav' => $data['nav'],
            'is_own' => $data['isOwnCalendar'],
            'people_label' => $data['peopleLabel'],
            'people' => $data['allTechnicians']->map($person)->values(),
        ];

        if ($data['view'] === 'day') {
            return response()->json($base + [
                'rows' => collect($data['rows'])->map(fn ($row) => [
                    'technician' => $person($row['technician']),
                    'on_leave' => (bool) $row['on_leave'],
                    'axis_start' => $row['axis_start']->toIso8601String(),
                    'axis_end' => $row['axis_end']->toIso8601String(),
                    'lunch' => ['left_pct' => $row['lunch_left_pct'], 'width_pct' => $row['lunch_width_pct']],
                    'stats' => $row['stats'],
                    'blocks' => collect($row['blocks'])->map(fn ($b) => [
                        'left_pct' => $b['left_pct'],
                        'width_pct' => $b['width_pct'],
                        'is_overtime' => $b['is_overtime'],
                        'ticket' => [
                            'id' => $b['ticket']->id,
                            'ticket_number' => $b['ticket']->ticket_number,
                            'subject' => $b['ticket']->subject,
                            'status' => $b['ticket']->status,
                            'priority' => $b['ticket']->ticket_type,
                            'scheduled_start' => $b['ticket']->scheduled_start?->toIso8601String(),
                            'scheduled_end' => $b['ticket']->scheduled_end?->toIso8601String(),
                        ],
                    ])->values(),
                ])->values(),
            ]);
        }

        return response()->json($base + [
            'columns' => collect($data['columns'])->map(fn ($c) => $c instanceof Carbon ? $c->format('Y-m-d') : (string) $c)->values(),
            'rows' => collect($data['rows'])->map(fn ($row) => [
                'technician' => $person($row['technician']),
                'cells' => collect($row['cells'])->map(fn ($cell) => [
                    'date' => isset($cell['date']) ? ($cell['date'] instanceof Carbon ? $cell['date']->format('Y-m-d') : (string) $cell['date']) : null,
                    'on_leave' => (bool) ($cell['on_leave'] ?? false),
                    'allocated_minutes' => (int) ($cell['allocated_minutes'] ?? 0),
                    'ticket_count' => (int) ($cell['ticket_count'] ?? 0),
                    'has_overtime' => (bool) ($cell['has_overtime'] ?? false),
                    'workload_pct' => (float) ($cell['workload_pct'] ?? 0),
                    'level' => $cell['level'] ?? null,
                ])->values(),
            ])->values(),
        ]);
    }
}
