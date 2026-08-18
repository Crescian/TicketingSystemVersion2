<?php

namespace App\Support;

// Mirrors the tracker step logic in resources/views/dashboard/employee.blade.php
// (the "Full lifecycle tracker" block) so the employee-facing status emails and the
// dashboard tracker never disagree about which of the 5 phases a ticket is in.
// Single source of truth for both — change one, change the other.
//
// Deliberately simplified to 5 broad phases — the employee side doesn't show the
// detailed internal workflow (see App\Support\TicketStatus for the 12 internal
// statuses staff actually work with).
class TicketTrackerStep
{
    public const LABELS = [
        1 => 'Submitted',
        2 => 'Scheduled',
        3 => 'In Progress',
        4 => 'Awaiting Your Confirmation',
        5 => 'Closed',
    ];

    // "Scheduled" — a specific technician and start time have been set (see
    // App\Services\TicketScheduler), but work hasn't actually started yet.
    private const SCHEDULED_STATUSES = [
        TicketStatus::ASSIGNED,
    ];

    // Everything from actively working it through the report sitting with a
    // supervisor for review/approval reads as one "In Progress" phase to the
    // requestor — they don't need the internal report-drafting/review detail.
    private const IN_PROGRESS_STATUSES = [
        TicketStatus::IN_PROGRESS_SERVICE_REQUEST,
        TicketStatus::CLOSED_SERVICE_REQUEST,
        TicketStatus::IN_PROGRESS_SERVICE_REPORT,
        TicketStatus::DONE_SERVICE_REPORT,
        TicketStatus::REPORT_FOR_REVIEW,
        TicketStatus::APPROVED_SERVICE_REPORT,
        TicketStatus::ESCALATED,
    ];

    // The highest tracker step (1-5) this ticket has reached, given its current
    // status. Returns null for Cancelled tickets, which don't show a tracker at
    // all (mirrors $isCancelled in the blade).
    public static function highestReached(?string $status, ?string $dateAcknowledged): ?int
    {
        if ($status === TicketStatus::CANCELLED) {
            return null;
        }

        return match (true) {
            $status === TicketStatus::CLOSED => 5,
            $status === TicketStatus::REQUESTOR_CONFIRMATION => 4,
            in_array($status, self::IN_PROGRESS_STATUSES, true) => 3,
            in_array($status, self::SCHEDULED_STATUSES, true) => 2,
            default => 1,
        };
    }
}
