<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Concerns\HasUuids;

class BusinessUnits extends Model
{
    protected $keyType = 'string';      // ← add this
    public $incrementing = false;       // ← add this
}
