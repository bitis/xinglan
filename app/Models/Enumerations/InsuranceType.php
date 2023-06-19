<?php

namespace App\Models\Enumerations;

enum InsuranceType: int
{
    case Car = 1;
    case Other = 2;

    public function name(): string
    {
        return match ($this) {
            InsuranceType::Car => '车险',
            InsuranceType::Other => '非车险',
        };
    }
}
