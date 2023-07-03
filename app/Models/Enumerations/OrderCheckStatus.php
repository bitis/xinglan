<?php

namespace App\Models\Enumerations;

use App\Models\Enumerations\Traits\EnumArray;

enum OrderCheckStatus : int
{

    use EnumArray;

    case DispatchCompany = 0;

    case Checking = 1;

    case FinishedCheck = 3;

    case Checked = 4;

    case Dispatched = 5;

    case Repairing = 6;

    case Repaired = 7;


    public function name(): string
    {
        return match ($this) {
            OrderCheckStatus::DispatchCompany => '派遣外协查勘',
            OrderCheckStatus::Checking => '查勘中',
            OrderCheckStatus::FinishedCheck => '完成查勘',
            OrderCheckStatus::Checked => '完成确认',
            OrderCheckStatus::Dispatched => '分派施工',
            OrderCheckStatus::Repairing => '开始施工',
            OrderCheckStatus::Repaired => '完成施工',
        };
    }
}
