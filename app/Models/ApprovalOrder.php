<?php

namespace App\Models;

use App\Models\Enumerations\ApprovalType;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ApprovalOrder extends Model
{
    use HasFactory;

    protected $fillable = [
        'order_id',
        'company_id',
        'approval_type',
        'completed_at',
        'creator_id',
        'creator_name',
        'history',
        'latest_operator_id',
        'latest_operator_name',
        'latest_operator_status',
        'urging',
        'is_cancel'
    ];

    public function process(): HasMany
    {
        return $this->hasMany(ApprovalOrderProcess::class, 'approval_order_id', 'id');
    }

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class, 'order_id', 'id');
    }

    public static function getTypeText($approvalType): string
    {
        return match ($approvalType) {
            ApprovalType::ApprovalQuotation->value => '对外报价审核',
            ApprovalType::ApprovalAssessment->value => '核价（定损）审核',
            ApprovalType::ApprovalClose->value => '关闭工单审核',
            ApprovalType::ApprovalRepairCost->value => '施工修复成本审核',
            ApprovalType::ApprovalRepaired->value => '已修复资料审核',
            ApprovalType::ApprovalPayment->value => '付款审核',
        };
    }
}
