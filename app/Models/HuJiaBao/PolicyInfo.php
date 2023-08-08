<?php

namespace App\Models\HuJiaBao;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasOne;

/**
 * 保单信息
 */
class PolicyInfo extends Model
{
    use HasFactory;

    protected $table = 'hjb_policy_infos';


    public function property(): HasOne
    {
        return $this->hasOne(Property::class, 'policy_info_id', 'id');
    }
}
