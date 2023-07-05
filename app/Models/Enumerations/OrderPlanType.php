<?php

namespace App\Models\Enumerations;

use App\Models\Enumerations\Traits\EnumArray;

enum OrderPlanType : int
{

    use EnumArray;

    case Repair = 1;

    case TradeOff = 2;

    public function name(): string
    {
        return match ($this) {
            OrderPlanType::Repair => '施工修复',
            OrderPlanType::TradeOff => '协调处理',
        };
    }
}
