<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class LossItem extends Model
{
    use HasFactory;

    protected $table = 'hjb_loss_items';

    protected $fillable = [
        'investigation_info_id',
        'SequenceNo',
        'LossItemName',
        'LossItemType',
        'BenefitCode',
        'Number',
        'UnitPrice',
        'Salvage',
        'EstimatedAmount',
        'Remark',
    ];

    public function investigationInfo()
    {
        return $this->belongsTo(InvestigationInfo::class, 'investigation_info_id', 'id');
    }
}
