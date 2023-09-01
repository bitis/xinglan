<?php

namespace App\Models;

use App\Models\Traits\DefaultDatetimeFormat;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CalculationInfo extends Model
{
    use HasFactory, DefaultDatetimeFormat;

    protected $table = 'hjb_calculation_infos';

    protected $hidden = ['id', 'task_id', 'created_at', 'updated_at'];

    protected $fillable = [
        'task_id',
        'OperationType',
        'SequenceNo',
        'CalculationTimes',
        'ReserveType',
        'BenefitCode',
        'RequestedAmount',
        'Deductible',
        'AccidentLiabilityRatio',
        'PreviousRecognizedAmount',
        'TotalRecognizedAmount',
        'PreviousAdjustedAmount',
        'CalculationAmount',
        'AdjustedAmount',
        'TotalAdjustedAmount',
        'CalculationFormula',
        'IsDeclined',
    ];
}
