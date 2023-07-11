<?php

namespace App\Models\Enumerations;

use App\Models\Enumerations\Traits\EnumArray;

enum ApprovalStatus: int
{
    use EnumArray;

    case Pending = 0;

    case Accepted = 1;
    case Rejected = 2;

    public function name(): string
    {
        return match ($this) {
            ApprovalStatus::Pending => '未审核',
            ApprovalStatus::Accepted => '同意',
            ApprovalStatus::Rejected => '拒绝'
        };
    }
}
