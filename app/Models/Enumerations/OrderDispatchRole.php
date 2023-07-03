<?php

namespace App\Models\Enumerations;

use App\Models\Enumerations\Traits\EnumArray;

enum OrderDispatchRole : int
{

    use EnumArray;

    case Queue = 0;
    case Area = 1;

    public function name(): string
    {
        return match ($this) {
            OrderDispatchRole::Queue => '顺序分派',
            OrderDispatchRole::Area => '区域分派',
        };
    }
}
