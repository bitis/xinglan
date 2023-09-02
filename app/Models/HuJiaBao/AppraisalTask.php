<?php

namespace App\Models\HuJiaBao;

use App\Models\CalculationInfo;
use App\Models\PayeeInfo;
use App\Models\Traits\DefaultDatetimeFormat;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class AppraisalTask extends Model
{
    use HasFactory, DefaultDatetimeFormat;

    protected $table = 'hjb_appraisal_tasks';

    protected $fillable = [
        'ClaimNo',
        'TaskID',
        'SubClaim',
        'DueDate',
        'CurrentCalculationTime',
        'IsConfirmed',
        'DispatcherName',
        'DispatcherTel',
        'Remark',
        'status',
        'CalculationTimes',
        'IsDeclined',
        'AppraisalPassAt'
    ];

    public function info(): HasOne
    {
        return $this->hasOne(AppraisalInfo::class, 'task_id', 'id');
    }

    public function calculationInfoList(): HasMany
    {
        return $this->hasMany(CalculationInfo::class, 'task_id', 'id');
    }

    public function payeeInfoList(): HasMany
    {
        return $this->hasMany(PayeeInfo::class, 'task_id', 'id');
    }

    public function files(): HasMany
    {
        return $this->hasMany(Files::class, 'BusinessNo', 'ClaimNo');
    }
}
