<?php

namespace App\Models\HuJiaBao;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * 房屋标的信息 隶属于保单信息 PolicyInfo
 */
class Property extends Model
{
    use HasFactory;

    protected $table = 'hjb_properties';

    public function policyInfo(): BelongsTo
    {
        return $this->belongsTo(PolicyInfo::class, 'policy_info_id', 'id');
    }

    public function CoverageList(): HasMany
    {
        return $this->hasMany(Coverage::class, 'property_id', 'id');
    }
}
