<?php

namespace App\Models;

use App\Models\Traits\DefaultDatetimeFormat;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class GoodsPriceCat extends Model
{
    use HasFactory, DefaultDatetimeFormat;

    protected $fillable = [
        'id',
        'parent_id',
        'name',
        'level',
    ];

    public function children(): HasMany
    {
        return $this->hasMany(GoodsPriceCat::class, 'parent_id', 'id');
    }
}
