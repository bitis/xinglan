<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class RepairTask extends Model
{
    use HasFactory;

    protected $fillable = [
        'repair_plan_id',
        'goods_type',
        'goods_name',
        'remark',
        'repair_company_id',
        'repair_company_name',
        'repair_cost',
        'image',
        'wusun_confirmed',
        'repair_user_id',
        'repair_user_name',
    ];
}
