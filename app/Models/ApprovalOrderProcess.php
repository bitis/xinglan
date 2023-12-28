<?php

namespace App\Models;

use App\Models\Traits\DefaultDatetimeFormat;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;

class ApprovalOrderProcess extends Model
{
    use HasFactory, DefaultDatetimeFormat;

    protected $fillable = [
        'approval_order_id',
        'company_id',
        'user_id',
        'name',
        'creator_id',
        'creator_name',
        'step',
        'approval_status',
        'remark',
        'completed_at',
        'hidden',
        'mode',
        'approval_type',
        'order_id'
    ];

    public function approvalOrder(): HasOne
    {
        return $this->hasOne(ApprovalOrder::class, 'id', 'approval_order_id');
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class, 'company_id', 'id');
    }

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class, 'order_id', 'id')
//            ->select('*')
            ->without('lossPersons');
    }
}
