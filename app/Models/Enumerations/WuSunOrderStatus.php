<?php

namespace App\Models\Enumerations;

use App\Models\Enumerations\Traits\EnumArray;

enum WuSunOrderStatus : int
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
            WuSunOrderStatus::AcceptCheck => '接受任务',
            WuSunOrderStatus::ArrivedCheckLocation => '抵达现场',
            WuSunOrderStatus::FinishedCheck => '完成查勘',
            WuSunOrderStatus::Checked => '完成确认',
            WuSunOrderStatus::Dispatched => '分派施工',
            WuSunOrderStatus::Repairing => '开始施工',
            WuSunOrderStatus::Repaired => '完成施工',
        };
    }
}
