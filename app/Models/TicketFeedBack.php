<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

class TicketFeedback extends Model
{
    use HasUuids;

    public $timestamps = false;
    const UPDATED_AT = null;
    const CREATED_AT = 'created_at';

    protected $table = 'ticket_feed_backs'; // ← add this line
    protected $fillable = [
        'ticket_id',
        'user_id',
        'rating',
        'comments',
        'created_at',
    ];

    protected $casts = [
        'rating' => 'integer',
        'created_at' => 'datetime',
    ];

    public function ticket()
    {
        return $this->belongsTo(Tickets::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}