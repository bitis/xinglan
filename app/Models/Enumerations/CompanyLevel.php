<?php

namespace App\Models\Enumerations;

use App\Models\Enumerations\Traits\EnumArray;

enum CompanyLevel: int
{

    use EnumArray;

    case One = 1;
    case Second = 2;
    case Three = 3;

    public function name(): string
    {
        return match ($this) {
            CompanyLevel::One => '一级部门',
            CompanyLevel::Second => '二级部门',
            CompanyLevel::Three => '三级部门',
        };
    }
}
