<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class InvestigationInfo extends Model
{
    use HasFactory;

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

    public function lossItemList()
    {
        return $this->hasMany(LossItem::class, 'investigation_info_id', 'id');
    }
}
