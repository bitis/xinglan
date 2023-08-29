<?php

namespace App\Models;

use App\Models\Traits\DefaultDatetimeFormat;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class IndemnityInfo extends Model
{
    use HasFactory, DefaultDatetimeFormat;

    protected $table = 'hjb_indemnity_infos';

    protected $fillable = [
        'payee_info_id',
        'SequenceNo',
        'ReserveType',
        'BenefitCode',
        'UnrecognizedAmount',
        'IndemnityAmount',
        'Remark',
    ];
}
