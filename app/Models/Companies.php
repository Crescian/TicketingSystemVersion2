<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Concerns\HasUuids;

class Companies extends Model
{
    use HasUuids;

    protected $keyType = 'string';      // ← add this
    public $incrementing = false;       // ← add this
    public function businessUnit()
    {
        return $this->belongsTo(BusinessUnits::class, 'business_units_id');
    }

}
