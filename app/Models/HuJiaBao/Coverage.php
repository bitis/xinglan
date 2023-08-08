<?php

namespace App\Models\HuJiaBao;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * 险别信息 隶属于房屋标的信息 Property
 */
class Coverage extends Model
{
    use HasFactory;

    protected $table = 'hjb_coverages';

    public function property(): BelongsTo
    {
        return $this->belongsTo(Property::class, 'property_id', 'id');
    }

    public function benefitList(): HasMany
    {
        return $this->hasMany(Benefit::class, 'coverage_id', 'id');
    }
}
