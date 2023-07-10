<?php

namespace App\Models\Enumerations;

use App\Models\Enumerations\Traits\EnumArray;

enum ApprovalMode : int
{

    use EnumArray;

    case OR = 1;

    case QUEUE = 2;


    public function name(): string
    {
        return match ($this) {
            ApprovalMode::OR => '或签',
            ApprovalMode::QUEUE => '依次审批',
        };
    }
}
