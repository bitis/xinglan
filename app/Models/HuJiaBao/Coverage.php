<?php

namespace App\Models\HuJiaBao;

use App\Models\Traits\DefaultDatetimeFormat;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * 险别信息 隶属于房屋标的信息 Property
 */
class Coverage extends Model
{
    use HasFactory, DefaultDatetimeFormat;

    protected $table = 'hjb_coverages';

    protected $fillable = [
        'IsFinalLevelCt',
        'CoverageCode',
        'SumInsured',
        'SumPaymentAmt'
    ];

    public function property(): BelongsTo
    {
        return $this->belongsTo(Property::class, 'property_id', 'id');
    }

    public function benefitList(): HasMany
    {
        return $this->hasMany(Benefit::class, 'coverage_id', 'id');
    }
}
