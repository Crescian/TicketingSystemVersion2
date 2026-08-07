<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

class WorkloadClass extends Model
{
    use HasUuids;

    protected $fillable = [
        'name',
        'typical_application',
        'response_minutes',
        'resolution_minutes',
        'response_label',
        'resolution_label',
        'requires_manual_resolution',
        'is_active',
        'sort_order',
    ];

    protected $casts = [
        'response_minutes' => 'integer',
        'resolution_minutes' => 'integer',
        'requires_manual_resolution' => 'boolean',
        'is_active' => 'boolean',
        'sort_order' => 'integer',
    ];

    public function tickets()
    {
        return $this->hasMany(Tickets::class, 'workload_class_id');
    }
}
