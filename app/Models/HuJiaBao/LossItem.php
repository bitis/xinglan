<?php

namespace App\Models\HuJiaBao;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

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

    public function investigationInfo(): BelongsTo
    {
        return $this->belongsTo(InvestigationInfo::class, 'investigation_info_id', 'id');
    }
}
