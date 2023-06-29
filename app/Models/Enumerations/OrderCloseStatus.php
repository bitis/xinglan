<?php

namespace App\Models\Enumerations;

use App\Models\Enumerations\Traits\EnumArray;

enum OrderCloseStatus : int
{

    use EnumArray;

    case Pursuance = 0;

    case CloseCheck = 1;

    case Closed = 2;

    public function name(): string
    {
        return match ($this) {
            OrderCloseStatus::Pursuance => '未结案',
            OrderCloseStatus::CloseCheck => '结案审核中',
            OrderCloseStatus::Closed => '已结案',
        };
    }
}
