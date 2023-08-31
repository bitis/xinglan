<?php

namespace App\Models\HuJiaBao;

use App\Models\Traits\DefaultDatetimeFormat;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasOne;

class AppraisalTask extends Model
{
    use HasFactory, DefaultDatetimeFormat;

    protected $table = 'hjb_appraisal_tasks';

    protected $fillable = [
        'ClaimNo',
        'TaskID',
        'DueDate',
        'IsConfirmed',
        'Remark',
        'DispatcherName',
        'DispatcherTel',
        'status',
    ];

    public function info(): HasOne
    {
        return $this->hasOne(AppraisalInfo::class, 'task_id', 'id');
    }
}
