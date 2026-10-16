<?php

namespace Tests\Feature\Staff;

use App\Http\Controllers\Dashboard\SupervisorDashboardController;
use App\Support\TicketStatus;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class SupervisorDashboardTest extends StaffTestCase
{
    // The per-row capacity annotation used to reuse $status, so any listed
    // ticket with an assignee replaced the selected tab with that technician's
    // schedule status ("available", "busy", …).
    public function test_support_dashboard_keeps_the_selected_tab(): void
    {
        $supervisor = $this->staff('Supervisor - Support Specialist');
        $tech = $this->staff('IT Support Specialist');
        $this->staffTicket(['status' => TicketStatus::IN_PROGRESS_SERVICE_REQUEST, 'pending_role' => null, 'assigned_to' => $tech->id]);
        Auth::login($supervisor);

        $view = app(SupervisorDashboardController::class)->supportIndex(Request::create('/supervisor/support/dashboard', 'GET', ['status' => 'in-progress']));

        $this->assertSame('in-progress', $view->getData()['status']);
        $this->assertSame(1, $view->getData()['tickets']->total());
    }
}
