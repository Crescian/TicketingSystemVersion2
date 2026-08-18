<?php

namespace App\Support;

// The 12 standard internal (ICT-staff-facing) ticket statuses, shared across
// every resolver track (Helpdesk, Technician, IT Admin, Manager) instead of
// each track having its own status strings. Which track/individual a ticket
// currently belongs to is no longer encoded in the status string itself —
// see Tickets::pending_role (queue-level, no assignee yet) and
// assignedTo.role (once assigned) for that.
//
// Cancelled is deliberately not in this set: it's a void/exception outcome,
// not a step in the request-fulfillment pipeline.
class TicketStatus
{
    public const FOR_ACKNOWLEDGMENT = 'For Acknowledgment';
    // Helpdesk-specific: set once Helpdesk acknowledges a fresh ticket
    // (pending_role QUEUE_HELPDESK only) — marks it as "seen, now needs
    // classifying" rather than leaving it looking untouched. Not part of the
    // shared 12-status pipeline; other queues' acknowledge actions (Support/
    // Admin Supervisor) don't have an equivalent and stay at FOR_ACKNOWLEDGMENT.
    public const FOR_CLASSIFICATION = 'For Classification';
    public const CLASSIFIED = 'Classified';
    public const ASSIGNED = 'Assigned';
    public const IN_PROGRESS_SERVICE_REQUEST = 'In Progress Service Request';
    public const CLOSED_SERVICE_REQUEST = 'Closed Service Request';
    public const IN_PROGRESS_SERVICE_REPORT = 'In Progress Service Report';
    public const DONE_SERVICE_REPORT = 'Done Service Report';
    public const REPORT_FOR_REVIEW = 'Report For Review';
    public const APPROVED_SERVICE_REPORT = 'Approved Service Report';
    public const ESCALATED = 'Escalated';
    public const REQUESTOR_CONFIRMATION = 'Requestor Confirmation';
    public const CLOSED = 'Closed';

    public const CANCELLED = 'Cancelled';

    public const PIPELINE = [
        self::FOR_ACKNOWLEDGMENT,
        self::CLASSIFIED,
        self::ASSIGNED,
        self::IN_PROGRESS_SERVICE_REQUEST,
        self::CLOSED_SERVICE_REQUEST,
        self::IN_PROGRESS_SERVICE_REPORT,
        self::DONE_SERVICE_REPORT,
        self::REPORT_FOR_REVIEW,
        self::APPROVED_SERVICE_REPORT,
        self::ESCALATED,
        self::REQUESTOR_CONFIRMATION,
        self::CLOSED,
    ];

    // Role-queue values for Tickets::pending_role — mirrors the role_name
    // values in RoleSeeder / CheckSlaTimers::RESOLVER_ROLE_SUPERVISOR. Set
    // while a ticket sits in a queue (FOR_ACKNOWLEDGMENT/CLASSIFIED) with no
    // specific assignee yet.
    public const QUEUE_HELPDESK = 'Helpdesk';
    public const QUEUE_SUPPORT_SUPERVISOR = 'Supervisor - Support Specialist';
    public const QUEUE_ADMIN_SUPERVISOR = 'Supervisor - IT Admin';
    public const QUEUE_MANAGER = 'Manager';
}
