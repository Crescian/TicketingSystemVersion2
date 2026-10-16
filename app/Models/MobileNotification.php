<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

// A staff member's in-app notification (see StaffNotifier).
class MobileNotification extends Model
{
    use HasUuids;

    public const UPDATED_AT = null;

    protected $fillable = ['user_id', 'type', 'title', 'body', 'ticket_id', 'concern_id', 'read_at'];

    protected $casts = ['read_at' => 'datetime', 'created_at' => 'datetime'];
}
