<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Spatie\Permission\Traits\HasPermissions;

class Permission extends \Spatie\Permission\Models\Permission
{
    use HasFactory, HasPermissions;

    const OPERATE_ALL = '*';
    const OPERATE_VIEW = 'view';
    const OPERATE_CREATE = 'create';
    const OPERATE_DELETE = 'delete';
    const OPERATE_EXPORT = 'export';
}
