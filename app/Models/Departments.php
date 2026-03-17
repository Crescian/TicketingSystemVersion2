<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

class Departments extends Model
{
    use HasUuids;

    public $timestamps = false;

    protected $fillable = ['companies_id', 'department_name'];

    public function users()
    {
        return $this->hasMany(User::class, 'department_id');
    }
}
