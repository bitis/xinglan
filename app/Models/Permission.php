<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Spatie\Permission\Traits\HasPermissions;

class Permission extends \Spatie\Permission\Models\Permission
{
    use HasFactory, HasPermissions;

    protected $hidden = ['created_at', 'updated_at', 'pivot'];
}
