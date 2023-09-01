<?php

namespace App\Models;

use App\Models\Traits\DefaultDatetimeFormat;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class PayeeInfo extends Model
{
    use HasFactory, DefaultDatetimeFormat;

    protected $table = 'hjb_payee_infos';

    protected $hidden = ['id', 'task_id', 'created_at', 'updated_at'];

    protected $fillable = [
        'appraisal_info_id',
        'OperationType',
        'SequenceNo',
        'CalculationTimes',
        'PayeeName',
        'PayMode',
        'AccountType',
        'BankCode',
        'BankName',
        'OpenAccountBranchName',
        'AccountName',
        'BankCardNo',
        'TotalIndemnityAmount',
        'IndemnityInfoList'
    ];

    protected $casts = [
        'IndemnityInfoList' => 'array'
    ];

    public function indemnity(): HasMany
    {
        return $this->hasMany(IndemnityInfo::class, 'payee_info_id', 'id');
    }
}
