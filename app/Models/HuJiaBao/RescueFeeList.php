<?php

namespace App\Models\HuJiaBao;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class RescueFeeList extends Model
{
    use HasFactory;

    protected $table = 'hjb_rescue_fees';

    protected $hidden = ['id', 'appraisal_info_id', 'created_at', 'updated_at'];

    protected $fillable = [
        'appraisal_info_id',
        'OperationType',
        'SequenceNo',
        'AppraisalTimes',
        'RescueUnit',
        'BenefitCode',
        'RescueAmount',
        'Remark',
    ];
}
