<?php

namespace App\Models\Enumerations;

use App\Models\Enumerations\Traits\EnumArray;

enum WuSunCheckStatus : int
{

    use EnumArray;

    case AcceptCheck = 1;

    case ArrivedCheckLocation = 2;

    case FinishedCheck = 3;

    case Checked = 4;

    case Dispatched = 5;

    case Repairing = 6;

    case Repaired = 7;


    public function name(): string
    {
        return match ($this) {
            WuSunCheckStatus::AcceptCheck => '接受任务',
            WuSunCheckStatus::ArrivedCheckLocation => '抵达现场',
            WuSunCheckStatus::FinishedCheck => '完成查勘',
            WuSunCheckStatus::Checked => '完成确认',
            WuSunCheckStatus::Dispatched => '分派施工',
            WuSunCheckStatus::Repairing => '开始施工',
            WuSunCheckStatus::Repaired => '完成施工',
        };
    }
}
