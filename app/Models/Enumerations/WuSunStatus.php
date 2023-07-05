<?php

namespace App\Models\Enumerations;

use App\Models\Enumerations\Traits\EnumArray;

enum WuSunStatus : int
{

    use EnumArray;

    case AcceptCheck = 1;

    case FinishedCheck = 2;

    case CheckedPlan = 3;

    case Dispatched = 4;

    case Repairing = 5;

    case Repaired = 6;

    public function name(): string
    {
        return match ($this) {
            WuSunStatus::AcceptCheck => '接受任务',
            WuSunStatus::FinishedCheck => '完成查勘',
            WuSunStatus::CheckedPlan => '确认方案',
            WuSunStatus::Dispatched => '分派施工',
            WuSunStatus::Repairing => '开始施工',
            WuSunStatus::Repaired => '完成施工',
        };
    }
}
