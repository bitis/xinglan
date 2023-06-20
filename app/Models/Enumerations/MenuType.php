<?php

namespace App\Models\Enumerations;

enum MenuType: int
{
    case Folder = 1;
    case Menu = 2;
    case Button = 3;

    public function name(): string
    {
        return match ($this) {
            MenuType::Folder => '文件夹',
            MenuType::Menu => '目录',
            MenuType::Button => '按钮',
        };
    }
}
