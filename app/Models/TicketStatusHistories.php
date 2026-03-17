<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
class TicketStatusHistories extends Model
{
    use HasUuids;

    public $timestamps = false;

    protected $fillable = [
        'ticket_id',
        'old_status',
        'new_status',
        'changed_by',
        'notes',
        'changed_at',
    ];

    protected $casts = [
        'changed_at' => 'datetime',
    ];

    public function ticket()
    {
        return $this->belongsTo(Tickets::class, 'ticket_id');
    }

    public function changedBy()
    {
        return $this->belongsTo(User::class, 'changed_by');
    }
}
