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

    protected $fillable = [
        'appraisal_info_id',
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
    ];

    public function indemnity(): HasMany
    {
        return $this->hasMany(IndemnityInfo::class, 'payee_info_id', 'id');
    }
}
