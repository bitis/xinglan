<?php

namespace App\Models\Enumerations;

use App\Models\Enumerations\Traits\EnumArray;

enum CheckStatus : int
{

    use EnumArray;

    case Wait = 0;

    case Accept = 1;

    case Denial = 2;

    public function name(): string
    {
        return match ($this) {
            CheckStatus::Wait => '等待审核',
            CheckStatus::Accept => '通过',
            CheckStatus::Denial => '拒绝',
        };
    }
}
