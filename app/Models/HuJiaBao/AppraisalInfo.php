<?php

namespace App\Models\HuJiaBao;

use App\Models\Traits\DefaultDatetimeFormat;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class AppraisalInfo extends Model
{
    use HasFactory, DefaultDatetimeFormat;

    protected $table = 'hjb_appraisal_infos';

    protected $fillable = [
        'task_id',
        'RiskName',
        'SubClaimType',
        'DamageObject',
        'IsConfirmed',
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
        'AppraisalType',
        'TotalLossAmount',
        'TotalRescueAmount',
        'Remark',
    ];

    public function lossItemList(): HasMany
    {
        return $this->hasMany(AppraisalLossItem::class, 'appraisal_info_id', 'id');
    }

    public function rescueFeeList(): HasMany
    {
        return $this->hasMany(RescueFeeList::class, 'appraisal_info_id', 'id');
    }
}
