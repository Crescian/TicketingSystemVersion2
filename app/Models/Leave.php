<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

class Leave extends Model
{
    use HasUuids;

    protected $fillable = [
        'technician_id',
        'date',
        'reason',
        'is_active',
    ];

    protected $casts = [
        'date' => 'date',
        'is_active' => 'boolean',
    ];

    public function technician()
    {
        return $this->belongsTo(User::class, 'technician_id');
    }

    // Per-request memoized leave lookup, keyed by technician + local calendar date —
    // mirrors App\Support\BusinessClock's isHolidayDate(), but exposed publicly since
    // leave is per-technician and can't live inside the stateless org-wide BusinessClock.
    // Called from App\Services\TicketScheduler's per-day walk, potentially many times
    // per request.
    private static array $leaveCache = [];

    public static function isOnLeave(User $technician, Carbon $date): bool
    {
        $key = $technician->id . '|' . $date->copy()->timezone('Asia/Manila')->format('Y-m-d');

        return self::$leaveCache[$key] ??= self::where('technician_id', $technician->id)
            ->where('date', $date->copy()->timezone('Asia/Manila')->format('Y-m-d'))
            ->where('is_active', true)
            ->exists();
    }
}
