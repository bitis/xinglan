<?php

namespace App\Models\Enumerations;

use App\Models\Enumerations\Traits\EnumArray;

enum Status: string
{
    use EnumArray;

    case Disable = '0';
    case Normal = '1';

    public function name(): string
    {
        return match ($this) {
            Status::Disable => '禁用',
            Status::Normal => '正常'
        };
    }
}
