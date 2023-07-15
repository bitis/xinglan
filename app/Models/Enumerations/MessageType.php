<?php

namespace App\Models\Enumerations;

use App\Models\Enumerations\Traits\EnumArray;

enum MessageType : int
{

    use EnumArray;

    case NewOrder = 0;
    case NewCheckTask = 1;
    case ConfirmedPrice = 2;

    public function name(): string
    {
        return match ($this) {
            MessageType::NewOrder => '新增订单',
            MessageType::NewCheckTask => '分派查勘任务',
            MessageType::ConfirmedPrice => '客户核价',
        };
    }

}
