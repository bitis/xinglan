<?php

namespace App\Models\HuJiaBao;

use App\Models\Traits\DefaultDatetimeFormat;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AppraisalLossItem extends Model
{
    use HasFactory, DefaultDatetimeFormat;

    protected $table = 'hjb_appraisal_loss_items';

    protected $fillable = [
        'appraisal_info_id',
        'SequenceNo',
        'AppraisalTimes',
        'LossItemName',
        'LossItemType',
        'BenefitCode',
        'Number',
        'UnitPrice',
        'Salvage',
        'LossAmount',
        'Remark',
    ];
}
