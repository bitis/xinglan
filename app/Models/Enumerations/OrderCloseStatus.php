<?php

namespace App\Models\Enumerations;

use App\Models\Enumerations\Traits\EnumArray;

enum OrderCloseStatus : int
{

    use EnumArray;

    case Wait = 0;

    case Check = 1;

    case Closed = 2;

    public function name(): string
    {
        return match ($this) {
            OrderCloseStatus::Wait => '未结案',
            OrderCloseStatus::Check => '结案审核中',
            OrderCloseStatus::Closed => '已结案',
        };
    }
}
