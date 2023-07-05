<?php

namespace App\Models\Enumerations;

use App\Models\Enumerations\Traits\EnumArray;

enum WuSunCheckStatus : int
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
            WuSunCheckStatus::AcceptCheck => '接受任务',
            WuSunCheckStatus::FinishedCheck => '完成查勘',
            WuSunCheckStatus::CheckedPlan => '确认方案',
            WuSunCheckStatus::Dispatched => '分派施工',
            WuSunCheckStatus::Repairing => '开始施工',
            WuSunCheckStatus::Repaired => '完成施工',
        };
    }
}
