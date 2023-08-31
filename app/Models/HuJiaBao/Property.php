<?php

namespace App\Models\HuJiaBao;

use App\Models\Traits\DefaultDatetimeFormat;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * 房屋标的信息 隶属于保单信息 PolicyInfo
 */
class Property extends Model
{
    use HasFactory, DefaultDatetimeFormat;

    protected $table = 'hjb_properties';

    protected $fillable = [
        "PropertyProvince",
        "PropertyCity",
        "PropertyDistrict",
        "PropertyDetailAddress",
    ];

    public function policyInfo(): BelongsTo
    {
        return $this->belongsTo(PolicyInfo::class, 'policy_info_id', 'id');
    }

    public function coverageList(): HasMany
    {
        return $this->hasMany(Coverage::class, 'property_id', 'id');
    }
}
