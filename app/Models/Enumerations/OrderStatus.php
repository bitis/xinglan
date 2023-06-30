<?php

namespace App\Models\Enumerations;

use App\Models\Enumerations\Traits\EnumArray;

enum OrderStatus : int
{

    use EnumArray;

    case WaitCheck = 0;

    case Checking = 1;

    case WaitRepair = 2;

    case Repairing = 3;

    case Repaired = 4;

    case Cancelled = 90;

    case Recycled = 91;

    case Mediated = 99;


    public function name(): string
    {
        return match ($this) {
            OrderStatus::WaitCheck => '待查勘',
            OrderStatus::Checking => '查勘中',
            OrderStatus::WaitRepair => '待施工',
            OrderStatus::Repairing => '施工中',
            OrderStatus::Repaired => '已修复',
            OrderStatus::Cancelled => '已取消',
            OrderStatus::Recycled => '已回收',
            OrderStatus::Mediated => '协调处理',
        };
    }
}
