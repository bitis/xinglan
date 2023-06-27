<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class GoodsPrice extends Model
{
    use HasFactory;

    protected $fillable = [
        'id',
        'company_id',
        'company_name',
        'province',
        'city',
        'region',
        'cat_id',
        'cat_name',
        'cat_parent_id',
        'product_name',
        'spec',
        'unit',
        'brand',
        'unit_price',
        'describe_image',
        'remark',
        'status',
    ];
}
