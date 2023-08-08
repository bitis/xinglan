<?php

namespace App\Models\HuJiaBao;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;

/**
 * 案件信息
 */
class ClaimInfo extends Model
{
    use HasFactory;

    protected $table = 'hjb_claim_infos';


    public function subClaimInfo(): HasOne
    {
        return $this->hasOne(SubClaimInfo::class, 'claim_info_id', 'id');
    }
}
