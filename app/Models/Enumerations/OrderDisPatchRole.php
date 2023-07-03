<?php

namespace App\Models\Enumerations;

use App\Models\Enumerations\Traits\EnumArray;

enum OrderDisPatchRole : int
{

    use EnumArray;

    case Order = 0;
    case Area = 1;

    public function name(): string
    {
        return match ($this) {
            OrderDisPatchRole::Order => '顺序分派',
            OrderDisPatchRole::Area => '区域分派',
        };
    }
}
