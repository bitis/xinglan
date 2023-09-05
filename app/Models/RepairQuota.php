<?php

namespace App\Models;

use App\Models\Traits\DefaultDatetimeFormat;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class RepairQuota extends Model
{
    use HasFactory, DefaultDatetimeFormat;

    protected $fillable = [
        'order_id',
        'repair_company_id',
        'repair_company_name',
        'total_price',
        'repair_days',
        'images',
        'submit_at',
        'operator_id',
        'operator_name',
        'win',
        'quota_type',
        'quota_finished_at',
        'remark',
    ];

    protected $casts = [
        'images' => 'array'
    ];

    const TYPE_SELF = 0; // 维修自主报价
    const TYPE_CHOOSE = 1; // 物损指派
}
