<?php

namespace App\Models;

use App\Models\Traits\DefaultDatetimeFormat;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class FinancialOrder extends Model
{
    use HasFactory, DefaultDatetimeFormat;

    protected $fillable = [
        'type',
        'company_id',
        'company_name',
        'case_number',
        'province',
        'city',
        'area',
        'address',
        'post_time',
        'license_plate',
        'vin',
        'insurance_check_phone',
        'insurance_check_name',
        'wusun_check_id',
        'wusun_check_name',
        'order_number',
        'order_id',
        'opposite_company_id',
        'opposite_company_name',
        'total_amount',
    ];

    const TYPE_RECEIPT = 1; // 收
    const TYPE_PAYMENT = 2; // 付

    const STATUS_WAIT = 1; // 待..
    const STATUS_PART = 2; // 部分..
    const STATUS_DONE = 3; // 已..
}
