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
        'creator_company_id',
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
        'accept_check_wusun_at',
        'wusun_company_id',
        'wusun_company_name',
        'confim_wusun_at',
        'wusun_check_id',
        'wusun_check_name',
        'wusun_check_phone',
        'wusun_check_accept_at',
        'wusun_checked_at',
        'wusun_status',
        'plan_type',
        'negotiation_content',
        'plan_confirm_at',
        'plan_checked_at',
        'wusun_repair_manager',
        'wusun_plan_confirm_remark',
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

    public function isBidOrder(): bool
    {
        return $this->bid_type == 1;
    }
}
