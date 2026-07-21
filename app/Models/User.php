<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasApiTokens, HasUuids, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'role_id',
        'department_id',
        'position',
        'active',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */

    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'active' => 'boolean',
        ];
    }

    // Relationship to Role
    public function role()
    {
        return $this->belongsTo(Role::class, 'role_id');
    }

    // Helper method to check role
    public function hasRole(string $role): bool
    {
        return $this->role?->role_name === $role;
    }

    // Active users holding a given role — used to fan out queue notifications.
    public function scopeWithActiveRole($query, string $roleName)
    {
        return $query->whereHas('role', fn($q) => $q->where('role_name', $roleName))
            ->where('active', true);
    }

    // Support-tier level (1-4) derived from the user's role, or null if unassigned/no tier.
    public function level(): ?int
    {
        return $this->role?->level;
    }

    // "L1"-"L4" label for display, or null for roles with no tier (e.g. Employee).
    public function levelLabel(): ?string
    {
        return $this->role?->level_label;
    }

    // Users holding a role at the given support-tier level (1-4) — used to fan out level-based notifications/assignment.
    public function scopeWithLevel($query, int $level)
    {
        return $query->whereHas('role', fn($q) => $q->where('level', $level));
    }
    public function assignedTickets()
    {
        return $this->hasMany(Tickets::class, 'assigned_to');
    }
    public function department()
    {
        return $this->belongsTo(\App\Models\Departments::class, 'department_id');
    }
}
