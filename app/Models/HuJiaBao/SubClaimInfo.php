<?php

namespace App\Models\HuJiaBao;

use App\Models\Traits\DefaultDatetimeFormat;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;

/**
 * 子赔案信息 隶属于案件信息ClaimInfo
 */
class SubClaimInfo extends Model
{
    use HasFactory, DefaultDatetimeFormat;

    protected $table = 'hjb_sub_claim_infos';

    protected $fillable = [
        'claim_info_id',
        'SubClaim',
        'RiskName',
        'SubClaimType',
        'DamageObject',
        'DamageDesc',
        'Owner',
        'TotalLoss',
        'CertiType',
        'CertiNo',
        'Sex',
        'DateOfBirth',
        'Mobile',
        'InjuryName',
        'InjuryType',
        'InjuryLevel',
        'DisabilityGrade',
        'Treatment',
        'HospitalName',
        'DateOfAdmission',
        'DateOfDischarge',
        'DaysInHospital',
        'CareName',
        'CareDays',
        'ContactProvince',
        'ContactCity',
        'ContactDistrict',
        'ContactDetailAddress',
    ];

    public static array $postback = [
        'ClaimNo',
        'TaskID',
        'SubClaim',
        'RiskName',
        'SubClaimType',
        'DamageObject',
        'Owner',
        'TotalLoss',
        'CertiType',
        'CertiNo',
        'Sex',
        'DateOfBirth',
        'Mobile',
        'InjuryName',
        'InjuryType',
        'InjuryLevel',
        'DisabilityGrade',
        'Treatment',
        'HospitalName',
        'DateOfAdmission',
        'DateOfDischarge',
        'DaysInHospital',
        'CareName',
        'CareDays',
        'ContactProvince',
        'ContactCity',
        'ContactDistrict',
        'ContactDetailAddress',
        'DamageDescription',
    ];

    public function claimInfo(): BelongsTo
    {
        return $this->belongsTo(ClaimInfo::class, 'claim_info_id', 'id');
    }

    public function taskInfo(): HasOne
    {
        return $this->hasOne(TaskInfo::class, 'sub_claim_info_id', 'id');
    }

    public function investigationInfo(): HasOne
    {
        return $this->hasOne(InvestigationInfo::class, 'sub_claim_info_id', 'id');
    }
}
