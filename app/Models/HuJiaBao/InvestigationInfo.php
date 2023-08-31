<?php

namespace App\Models\HuJiaBao;

use App\Models\Traits\DefaultDatetimeFormat;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class InvestigationInfo extends Model
{
    use HasFactory, DefaultDatetimeFormat;

    protected $table = 'hjb_investigation_infos';

    protected $fillable = [
        'PropertyNature',
        'IsInvolveRecovery',
        'InvestigatorContact',
        'InvestigatorArrivalDate',
        'InvestigationProvince',
        'InvestigationCity',
        'InvestigationDistrict',
        'InvestigationDetailAddress',
        'InvestigationDescription',
        'PropertyTotalEstimatedAmount',
        'Remark',
    ];

    public function lossItemList(): HasMany
    {
        return $this->hasMany(LossItem::class, 'investigation_info_id', 'id');
    }
}
