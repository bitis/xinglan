<?php

namespace App\Models\HuJiaBao;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;

/**
 * 责任信息 隶属于险别信息
 */
class Benefit extends Model
{
    use HasFactory;

    protected $table = 'hjb_benefits';

    public function coverage(): BelongsTo
    {
        return $this->belongsTo(Coverage::class, 'coverage_id', 'id');
    }

    public function claimInfo(): HasOne
    {
        return $this->hasOne(ClaimInfo::class, 'benefit_id', 'id');
    }
}
