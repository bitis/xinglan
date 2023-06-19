<?php

namespace App\Models;

use App\Models\Traits\DefaultDatetimeFormat;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Menu extends Model
{
    use HasFactory, DefaultDatetimeFormat;

    protected $fillable = ['parent_id', 'order', 'title', 'icon', 'uri'];

    public function roles(): BelongsToMany
    {
        return $this->belongsToMany('roles', 'role_menu', 'menu_id', 'role_id');
    }
}
