<?php

namespace App\Models\HuJiaBao;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * 任务信息 隶属于子赔案信息 SubClaimInfo
 */
class TaskInfo extends Model
{
    use HasFactory;

    protected $table = 'hjb_task_infos';

    protected $fillable = [
        'sub_claim_info_id',
        'TaskType',
        'TaskID',
        'DueDate',
        'InvestigationProvince',
        'InvestigationCity',
        'InvestigationDistrict',
        'InvestigationDetailAddress',
        'Remark',
    ];

    public function subClaimInfo(): BelongsTo
    {
        return $this->belongsTo(SubClaimInfo::class, 'sub_claim_info_id', 'id');
    }

    public function investigationInfo()
    {
        return $this->hasOne(InvestigationInfo::class, 'task_info_id', 'id');
    }
}
