<?php

namespace App\Models;

use App\Models\Traits\DefaultDatetimeFormat;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class History extends Model
{
    use HasFactory, DefaultDatetimeFormat;

    protected $fillable = [
        'province',
        'city',
        'area',
        'name',
        'specs',
        'unit',
        'price',
        'remark',
        'order_number',
        'created_at',
        'updated_at',
    ];

}
