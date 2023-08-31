<?php

namespace App\Models\HuJiaBao;

use App\Models\Traits\DefaultDatetimeFormat;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasOne;

/**
 * 保单信息
 */
class PolicyInfo extends Model
{
    use HasFactory, DefaultDatetimeFormat;

    protected $table = 'hjb_policy_infos';

    protected $fillable = [
        "PolicyNo",
        "ProductType",
        "EffectiveDate",
        "ExpireDate",
        "PolicyStatus",
        "StandardPremium"
    ];

    public function property(): HasOne
    {
        return $this->hasOne(Property::class, 'policy_info_id', 'id');
    }

    public function claimInfo(): HasOne
    {
        return $this->hasOne(ClaimInfo::class, 'policy_info_id', 'id');
    }
}
