<?php

namespace App\Models\Enumerations;

use App\Models\Enumerations\Traits\EnumArray;

enum InsuranceType: int
{
    use EnumArray;

    case Car = 1;
    case Other = 2;
    case CarPart = 3;

    public function name(): string
    {
        return match ($this) {
            InsuranceType::Car => '车险',
            InsuranceType::Other => '非车险',
            InsuranceType::CarPart => '配件',
        };
    }
}
