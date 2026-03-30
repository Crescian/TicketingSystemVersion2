<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Concerns\HasUuids;

class BusinessUnits extends Model
{
    use HasUuids;
    protected $keyType = 'string';      // ← add this
    public $incrementing = false;       // ← add this

    protected $fillable = ['business_units_name'];
    public $timestamps = false;
    public function companies()
    {
        return $this->hasMany(Companies::class, 'business_units_id');
    }
}
