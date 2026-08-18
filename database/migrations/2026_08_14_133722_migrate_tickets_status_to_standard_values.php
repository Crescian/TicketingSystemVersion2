<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

// One-time data migration: rewrites existing tickets.status values from the
// old per-track status strings to the 12 standard statuses in
// App\Support\TicketStatus (see plan: "Align internal ticket statuses").
// Runs as raw query-builder updates (no Eloquent model events/observers/mail).
// ticket_status_histories rows are intentionally left untouched — they're a
// historical audit log of what literally happened at the time, not rewritten.
return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // ── Queue-level statuses (no specific assignee yet): map to
        //    For Acknowledgment and backfill which role's queue owns them.
        DB::table('tickets')->where('status', 'New Request')
            ->update(['status' => 'For Acknowledgment', 'pending_role' => 'Helpdesk']);

        DB::table('tickets')->whereIn('status', ['Awaiting Supervisor', 'Awaiting Classification'])
            ->update(['status' => 'For Acknowledgment', 'pending_role' => 'Supervisor - Support Specialist']);

        DB::table('tickets')->whereIn('status', ['Awaiting Admin Supervisor', 'Awaiting Admin Classification'])
            ->update(['status' => 'For Acknowledgment', 'pending_role' => 'Supervisor - IT Admin']);

        DB::table('tickets')->where('status', 'Awaiting Manager')
            ->update(['status' => 'For Acknowledgment', 'pending_role' => 'Manager']);

        // ── Already has a specific assignee — disambiguated via assignedTo.role.
        DB::table('tickets')->whereIn('status', [
            'Awaiting Support Specialist Acknowledgement',
            'Awaiting Start SLA',
            'Awaiting Administrator Acknowledgement',
            'Awaiting Administrator SLA Start',
        ])->update(['status' => 'Assigned']);

        // ── Actively being worked.
        DB::table('tickets')->whereIn('status', [
            'L1 In Progress',
            'L1 Resolving',
            'In Progress',
            'Admin In Progress',
            'Manager In Progress',
        ])->update(['status' => 'In Progress Service Request']);

        // ── Drafting the service report.
        DB::table('tickets')->where('status', 'In Progress – Service Report')
            ->update(['status' => 'In Progress Service Report']);

        // ── Report submitted, was awaiting supervisor validation under the old
        //    single-step approve flow — lands in the new review queue.
        DB::table('tickets')->whereIn('status', ['Done – Service Report', 'Resolved'])
            ->update(['status' => 'Report For Review']);

        // ── Reclassification is no longer a ticket-level status (tracked solely
        //    on reclassification_requests.status) — restore to a generic
        //    in-progress bucket.
        DB::table('tickets')->where('status', 'Pending Reclassification')
            ->update(['status' => 'In Progress Service Request']);

        // ── Awaiting the requestor's confirmation.
        DB::table('tickets')->whereIn('status', ['Awaiting Requestor', 'Awaiting Your Confirmation'])
            ->update(['status' => 'Requestor Confirmation']);

        // Escalated, Closed, Cancelled are unchanged — no-op by design.
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Not reversible: several old statuses collapse onto the same new
        // status (e.g. Awaiting Admin Supervisor / Awaiting Admin
        // Classification both become For Acknowledgment), so the original
        // per-track distinction can't be reconstructed from the new value
        // alone. Roll back via a database backup instead.
    }
};
