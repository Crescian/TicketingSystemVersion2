<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

class Role extends Model
{
    use HasUuids;

    public $timestamps = false;

    protected $fillable = [
        'role_name',
        'description',
        'level',
    ];

    public function users()
    {
        return $this->hasMany(User::class, 'role_id');
    }

    // "L1"–"L4" support-tier label, or null for roles with no tier (e.g. Employee).
    public function getLevelLabelAttribute(): ?string
    {
        return $this->level ? "L{$this->level}" : null;
    }
}
