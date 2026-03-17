<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

class TicketFeedBack extends Model
{
    use HasUuids;

    public $timestamps = false;

    protected $fillable = [
        'ticket_id',
        'user_id',
        'rating',
        'comments',
        'created_at',
    ];

    public function ticket()
    {
        return $this->belongsTo(Tickets::class, 'ticket_id');
    }

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}
