<?php

namespace App\Models\Enumerations;

enum Status: string
{
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
