<?php

namespace App\Models\Enumerations;

use App\Models\Enumerations\Traits\EnumArray;

/**
 * 审批类型
 */
enum ApprovalType : int
{

    use EnumArray;

    case ApprovalQuotation = 1;
    case ApprovalAssessment = 2;
    case ApprovalNegotiate = 3;
    case ApprovalRepairCost = 4;
    case ApprovalRepaired = 5;
    case ApprovalPayment = 6;
    case ApprovalExpense  = 7;
    case ApprovalClose  = 8;

    public function name(): string
    {
        return match ($this) {
            ApprovalType::ApprovalQuotation => '对外报价审核',
            ApprovalType::ApprovalAssessment => '核价（定损）审核',
            ApprovalType::ApprovalNegotiate => '谈赔成本审核',
            ApprovalType::ApprovalRepairCost => '施工修复成本审核',
            ApprovalType::ApprovalRepaired => '已修复资料审核',
            ApprovalType::ApprovalPayment => '付款审核',
            ApprovalType::ApprovalClose => '接案审核',
        };
    }
}
