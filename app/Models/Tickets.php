<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

class Tickets extends Model
{
    use HasUuids;

    protected $fillable = [
        'ticket_number',
        'users_id',
        'assigned_to',
        'ticket_type',
        'request_category',
        'request_details',
        'asset',
        'subject',
        'concern',
        'status',
        'escalation_level',
        'location',
        'started_at',
        'resolved_at',
    ];

    protected $casts = [
        'started_at' => 'datetime',
        'resolved_at' => 'datetime',
    ];

    public function user()
    {
        return $this->belongsTo(User::class, 'users_id');
    }

    public function assignedTo()
    {
        return $this->belongsTo(User::class, 'assigned_to');
    }

    public function statusHistories()
    {
        return $this->hasMany(TicketStatusHistories::class, 'ticket_id');
    }

    public function feedback()
    {
        return $this->hasOne(TicketFeedBack::class, 'ticket_id');
    }


    // Helper: generate ticket number
    public static function generateTicketNumber(): string
    {
        $year = now()->year;
        $count = static::whereYear('created_at', $year)->count() + 1;
        return 'LGICT-' . $year . '-' . str_pad($count, 4, '0', STR_PAD_LEFT);
    }
    public function escalations()
    {
        return $this->hasMany(\App\Models\Escalations::class, 'ticket_id');
    }
    public function messages()
    {
        return $this->hasMany(TicketMessage::class, 'ticket_id')
            ->orderBy('created_at', 'asc');
    }

    public function unreadMessages()
    {
        return $this->hasMany(TicketMessage::class, 'ticket_id')
            ->where('is_read', false)
            ->where('sender_id', '!=', auth()->id());
    }
}
