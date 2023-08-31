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

    protected $fillable = [
        'policy_info_id',
        'ClaimNo',
        'AccidentTime',
        'ReportTime',
        'ReportDelayCause',
        'AccidentCause',
        'AccidentCauseDesc',
        'IsCatastrophe',
        'CatastropheCode',
        'PropertyLossAmt',
        'InjuryLossAmt',
        'ReportType',
        'ReportName',
        'ReportTel',
        'InsuredRelation',
        'AccidentProvince',
        'AccidentCity',
        'AccidentDistrict',
        'AccidentDetailAddress',
        'AccidentDesc',
    ];

    public function policyInfo(): BelongsTo
    {
        return $this->belongsTo(PolicyInfo::class, 'policy_info_id', 'id');
    }

    public function subClaimInfo(): HasOne
    {
        return $this->hasOne(SubClaimInfo::class, 'claim_info_id', 'id');
    }
}
