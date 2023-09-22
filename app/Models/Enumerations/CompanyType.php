<?php

namespace App\Models\Enumerations;

use App\Models\Company;
use App\Models\Enumerations\Traits\EnumArray;

enum CompanyType: int
{
    use EnumArray;

    case BaoXian = 1;
    case WuSun = 2;
    case WeiXiu = 3;
    case CheJian = 4;

    public function name(): string
    {
        return match ($this) {
            CompanyType::BaoXian => '保险公司',
            CompanyType::WuSun => '物损公司',
            CompanyType::WeiXiu => '维修公司',
            CompanyType::CheJian => '车件',
        };
    }
}
