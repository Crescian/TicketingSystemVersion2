<?php

namespace App\Support;

use App\Models\SlaRule;
use App\Models\WorkloadClass;

// Shared "what priority/response/resolution time does this classification actually
// resolve to" logic for the Classify & Assign action — used by both the regular
// Supervisor track (SupervisorDashboardController::classifyAndAssign) and the Admin
// escalation track (SupervisorDashboardController::adminClassifyAndAssign), so a
// per-ticket override (workload class, or a manually typed response/resolution
// time) works identically on both, instead of only being available on one.
class TicketSlaResolution
{
    public const VALIDATION_RULES = [
        'sla_rule_id' => 'required|exists:sla_rules,id',
        'priority' => 'nullable|in:Critical,High,Medium,Low',
        'workload_class_id' => 'nullable|exists:workload_classes,id',
        'response_time_minutes' => 'nullable|numeric|min:5|max:43200',
        'resolution_time_minutes' => 'nullable|numeric|min:5|max:43200',
    ];

    // Returns ['priority' => ..., 'response_time_minutes' => int, 'resolution_time_minutes' => int]
    // on success, or ['error' => string] if the combination is invalid — callers
    // check for the 'error' key and return back()->with('error', ...) themselves,
    // since each controller has its own redirect/withInput conventions.
    public static function resolve(
        SlaRule $slaRule,
        ?WorkloadClass $workloadClass,
        ?string $priorityOverride,
        ?int $responseOverride,
        ?int $resolutionOverride,
    ): array {
        if ($workloadClass && $workloadClass->requires_manual_resolution && $resolutionOverride === null) {
            return ['error' => "The \"{$workloadClass->name}\" workload class has no fixed resolution target — enter the agreed resolution time in minutes."];
        }

        $priority = $priorityOverride ?: $slaRule->priority;
        $responseTime = $responseOverride ?? ($workloadClass->response_minutes ?? $slaRule->response_time_minutes);
        $resolutionTime = $resolutionOverride ?? ($workloadClass->resolution_minutes ?? $slaRule->resolution_time_minutes);

        if ($responseTime >= $resolutionTime) {
            return ['error' => 'Response time must be less than resolution time.'];
        }

        return [
            'priority' => $priority,
            'response_time_minutes' => $responseTime,
            'resolution_time_minutes' => $resolutionTime,
        ];
    }

    // Human-readable audit-trail note when the resolved values differ from the SLA
    // rule's own defaults — empty string when nothing was actually overridden.
    public static function overrideNote(
        SlaRule $slaRule,
        string $priority,
        int $responseTime,
        int $resolutionTime,
        ?WorkloadClass $workloadClass,
    ): string {
        $wasOverridden = $priority !== $slaRule->priority
            || $responseTime !== $slaRule->response_time_minutes
            || $resolutionTime !== $slaRule->resolution_time_minutes;

        if (!$wasOverridden) {
            return '';
        }

        return " (SLA customized: {$priority} priority, {$responseTime}m response / {$resolutionTime}m resolution"
            . ($workloadClass ? ", workload class: {$workloadClass->name}" : '') . '.)';
    }
}
