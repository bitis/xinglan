<?php

namespace App\Models\Enumerations;

use App\Models\Enumerations\Traits\EnumArray;

enum ApprovalStatus: int
{
    use EnumArray;

    case Pending = 0;

    case Accept = 1;
    case Reject = 2;

    public function name(): string
    {
        return match ($this) {
            ApprovalStatus::Pending => '未审核',
            ApprovalStatus::Accept => '同意',
            ApprovalStatus::Reject => '拒绝'
        };
    }
}
