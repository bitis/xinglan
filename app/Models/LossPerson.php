<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class LossPerson extends Model
{
    use HasFactory;

    protected $fillable = ['order_id', 'goods_name', 'goods_types', 'owner_name', 'owner_phone'];
}
