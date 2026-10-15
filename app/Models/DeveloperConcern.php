<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

// A system concern (bug, feature request, data fix) sent by ICT staff straight to
// the Developer role. Kept apart from Tickets on purpose — it never enters SLA
// timers, support queues or executive reports. Lifecycle:
// Open → Acknowledged → Closed (closing an Open concern acknowledges it too).
class DeveloperConcern extends Model
{
    use HasUuids;

    public const OPEN = 'Open';
    public const ACKNOWLEDGED = 'Acknowledged';
    public const CLOSED = 'Closed';

    // Grouped for the form's <optgroup>s; each category maps to a "what to
    // include" hint shown under the dropdown. Concerns sent before this list
    // existed may still carry the old 'Bug' / 'Data Fix' values — category is a
    // plain string column, so those keep displaying as-is.
    public const CATEGORY_GROUPS = [
        'System Issues' => [
            'System Error / Bug' => 'Include the page, ticket #, the exact error message, and steps to reproduce.',
            'Page Not Loading / Slow' => 'Include the page, the time it happened, and whether it affects everyone or only you.',
            'Email / Notification Not Sent' => 'Include the ticket #, who should have received it, and the expected email.',
            'Wrong SLA / Timer Computation' => 'Include the ticket #, the time shown, and the time you expected.',
        ],
        'Data & Database' => [
            'Data Correction (wrong status, details, assignee)' => 'Include the ticket # or record, the current value, and the correct value.',
            'Database Change (new field / table / dropdown value)' => 'Include which table or screen, the field or value needed, and why.',
            'Delete / Restore Record' => 'Include the ticket # or record and the reason it should be deleted or restored.',
            'Data Export / Report Extraction' => 'Include the data needed, the date range, and the format (Excel, CSV, PDF).',
        ],
        'Network & Connectivity' => [
            'No Connection to LGPN (outside network OK)' => 'Include the PC name / IP, location, and what still works (internet? other internal sites?).',
            'No Internet Access (LGPN OK)' => 'Include the PC name / IP, location, and which sites fail to load.',
            'VPN / Remote Access Issue' => 'Include your location, the VPN client used, and the error shown.',
            'Server / Service Down' => 'Include the server or service name, when it went down, and who is affected.',
        ],
        'Accounts & Access' => [
            'Login / SSO Problem' => 'Include the account email, the login method (Microsoft / password), and the error shown.',
            'User Account / Role Change' => 'Include the user, their department, and the role or change needed.',
            'Permission / Access Rights' => 'Include the user, the page or action they cannot access, and why they need it.',
        ],
        'Requests & Enhancements' => [
            'Feature Request' => 'Describe what you need, who will use it, and the problem it solves.',
            'UI / Layout Change' => 'Include the page and what should change (a screenshot helps).',
            'Report / Dashboard Change' => 'Include the report or dashboard and the figures or columns needed.',
            'Integration / API Request' => 'Include the system to connect to, the data needed, and how often.',
        ],
        'Other' => [
            'Other' => 'Describe the concern in as much detail as possible.',
        ],
    ];

    public const URGENCIES = ['Low', 'Medium', 'High'];

    // ICT staff roles allowed to send concerns — Employees go through regular tickets.
    public const SENDER_ROLES = [
        'Helpdesk',
        'IT Support Specialist',
        'Supervisor - Support Specialist',
        'IT Admin',
        'Supervisor - IT Admin',
        'Manager',
    ];

    protected $fillable = [
        'reference',
        'submitted_by',
        'subject',
        'details',
        'category',
        'urgency',
        'status',
        'acknowledged_at',
        'closed_at',
        'closing_note',
    ];

    protected $casts = [
        'acknowledged_at' => 'datetime',
        'closed_at' => 'datetime',
    ];

    protected static function booted(): void
    {
        static::creating(function (DeveloperConcern $concern) {
            $concern->reference ??= static::generateReference();
            $concern->status ??= self::OPEN;
        });
    }

    // Every selectable category name, flattened out of CATEGORY_GROUPS.
    public static function categories(): array
    {
        return array_merge(...array_map('array_keys', array_values(self::CATEGORY_GROUPS)));
    }

    public function submitter()
    {
        return $this->belongsTo(User::class, 'submitted_by');
    }

    // "DEV-26-0001" — next suffix after this year's highest, same approach as
    // Tickets::generateTicketNumber(). Suffixes are zero-padded to a fixed width,
    // so the lexically highest reference is also the numerically highest.
    public static function generateReference(): string
    {
        $prefix = 'DEV-' . now()->format('y') . '-';

        $last = static::where('reference', 'like', $prefix . '%')->max('reference');
        $next = $last ? ((int) substr($last, strlen($prefix))) + 1 : 1;

        return $prefix . str_pad($next, 4, '0', STR_PAD_LEFT);
    }
}
