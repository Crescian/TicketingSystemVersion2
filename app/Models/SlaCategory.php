<?php
// app/Models/SlaCategory.php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

class SlaCategory extends Model
{
    use HasUuids;

    protected $table    = 'sla_categories';
    protected $fillable = ['name', 'icon', 'color', 'is_active', 'sort_order'];
    protected $casts    = ['is_active' => 'boolean', 'sort_order' => 'integer'];

    public function rules()
    {
        return $this->hasMany(SlaRule::class, 'sla_category_id')
                    ->orderByRaw("CASE priority WHEN 'High' THEN 1 WHEN 'Medium' THEN 2 WHEN 'Low' THEN 3 END")
                    ->orderBy('subcategory_name');
    }

    public function activeRules()
    {
        return $this->rules()->where('is_active', true);
    }
}

