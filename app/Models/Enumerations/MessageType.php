<?php

namespace App\Models\Enumerations;

use App\Models\Enumerations\Traits\EnumArray;

enum MessageType : int
{

    use EnumArray;

    case NewOrder = 0;
    case NewCheckTask = 1;

    public function name(): string
    {
        return match ($this) {
            MessageType::NewOrder => '新增订单',
            MessageType::NewCheckTask => '分派查勘任务',
        };
    }

}
