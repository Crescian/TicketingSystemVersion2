<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

class TicketMessage extends Model
{
    use HasUuids;

    public $timestamps = false;
    const UPDATED_AT = null;  // ← this stops Eloquent adding updated_at
    const CREATED_AT = 'created_at';

    protected $fillable = [
        'ticket_id',
        'sender_id',
        'message',
        'is_read',
        'read_at',
        'created_at',
    ];

    protected $casts = [
        'is_read' => 'boolean',
        'read_at' => 'datetime',
        'created_at' => 'datetime',
    ];

    public function ticket()
    {
        return $this->belongsTo(Tickets::class, 'ticket_id');
    }

    public function sender()
    {
        return $this->belongsTo(User::class, 'sender_id');
    }
}