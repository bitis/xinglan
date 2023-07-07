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


    public function name(): string
    {
        return match ($this) {
            ApprovalType::ApprovalQuotation => '对外报价审核',
            ApprovalType::ApprovalAssessment => '核价（定损）审核',
        };
    }
}
