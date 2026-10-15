<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

// An employee's phone registered for Expo push notifications (mobile app).
class UserDevice extends Model
{
    use HasUuids;

    protected $fillable = [
        'user_id',
        'expo_push_token',
        'platform',
        'last_seen_at',
    ];

    protected $casts = [
        'last_seen_at' => 'datetime',
    ];

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}
