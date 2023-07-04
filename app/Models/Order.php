<?php

namespace App\Models;

use App\Jobs\OrderDispatch;
use App\Models\Traits\DefaultDatetimeFormat;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Order extends Model
{
    use HasFactory, DefaultDatetimeFormat;

    protected $fillable = [
        'order_number',
        'insurance_company_id',
        'external_number',
        'case_number',
        'insurance_check_name',
        'insurance_check_phone',
        'post_time',
        'insurance_type',
        'license_plate',
        'vin',
        'locations',
        'province',
        'city',
        'area',
        'address',
        'creator_id',
        'creator_name',
        'insurance_people',
        'insurance_phone',
        'driver_name',
        'driver_phone',
        'remark',
        'customer_remark',
        'order_status',
        'close_status',
        'goods_types',
        'goods_name',
        'owner_name',
        'owner_phone',
        'owner_price',
        'images',
        'goods_remark',
        'check_wusun_company_id',
        'check_wusun_company_name',
        'dispatch_check_wusun_at',
        'wusun_company_id',
        'wusun_company_name',
        'confim_wusun_at',
        'wusun_check_id',
        'wusun_check_name',
        'wusun_check_phone',
        'wusun_check_accept_at',
        'check_status',
        'wusun_order_status',
        'dispatch_check_at',
        'dispatched',
    ];

    protected $casts = [
        'images' => 'array'
    ];

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class, 'insurance_company_id', 'id');
    }

    public static function genOrderNumber(): string
    {
        return 'XL' . date('ymdHis') . rand(10, 99);
    }

    protected static function booted()
    {
        static::created(function ($order) {
            OrderDispatch::dispatch($order);
        });
    }
}
