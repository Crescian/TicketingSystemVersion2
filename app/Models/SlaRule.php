<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

class SlaRule extends Model
{
    use HasUuids;

    protected $table = 'sla_rules';
    protected $fillable = [
        'sla_category_id',
        'subcategory_name',
        'priority',
        'response_time_minutes',
        'resolution_time_minutes',
        'is_active',
        'description',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'response_time_minutes' => 'float',
        'resolution_time_minutes' => 'float',
    ];

    public function category()
    {
        return $this->belongsTo(SlaCategory::class, 'sla_category_id');
    }

    public static function formatMinutes(float $minutes): string
    {
        if ($minutes < 60) {
            return $minutes . ' min' . ($minutes != 1 ? 's' : '');
        }
        $hours = $minutes / 60;
        if ($hours < 24) {
            $h = intval($hours);
            $m = intval(($hours - $h) * 60);
            return $h . 'h' . ($m > 0 ? ' ' . $m . 'm' : '');
        }
        $days = $hours / 24;
        $d = intval($days);
        $h = intval(($days - $d) * 24);
        return $d . 'd' . ($h > 0 ? ' ' . $h . 'h' : '');
    }

    public function isBreached(Tickets $ticket): bool
    {
        if (in_array($ticket->status, ['Resolved', 'Cancelled']))
            return false;
        $minutesOpen = $ticket->created_at->diffInMinutes(now());
        return $minutesOpen >= $this->resolution_time_minutes;
    }

    public function isAtRisk(Tickets $ticket): bool
    {
        if (in_array($ticket->status, ['Resolved', 'Cancelled']))
            return false;
        $minutesOpen = $ticket->created_at->diffInMinutes(now());
        return $minutesOpen >= ($this->resolution_time_minutes * 0.75)
            && $minutesOpen < $this->resolution_time_minutes;
    }
}