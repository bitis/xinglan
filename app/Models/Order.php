<?php

namespace App\Models;

use App\Jobs\OrderDispatch;
use App\Models\Enumerations\CheckStatus;
use App\Models\Traits\DefaultDatetimeFormat;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

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
        'creator_company_type',
        'insurance_people',
        'insurance_phone',
        'driver_name',
        'driver_phone',
        'remark',
        'customer_remark',
        'order_status',
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
        'dispatch_check_at',
        'dispatched',
        'bid_type',
        'bid_status',
        'confirmed_price',
        'confirmed_repair_days',
        'confirmed_remark',
        'confirmed_at',
        'confirm_user_id',
        'wusun_repair_user_id',
        'review_images',
        'review_remark',
        'review_at',
        'guarantee_period',
        'close_remark',
        'close_at',
        'close_status'
    ];

    protected $casts = [
        'images' => 'array'
    ];

    const BID_STATUS_PROGRESSING = 0;
    const BID_STATUS_FINISHED = 1;

    /**
     * 未查勘
     */
    const WUSUN_CHECK_STATUS_WAITING = 0;

    /**
     * 查勘中
     */
    const WUSUN_CHECK_STATUS_CHECKING = 1;

    /**
     * 查勘完成
     */
    const WUSUN_CHECK_STATUS_FINISHED = 2;



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

    public function quotations(): HasMany
    {
        return $this->hasMany(OrderQuotation::class)
            ->where('order_quotations.check_status', '=', CheckStatus::Accept->value);
    }

    public function quotation(): HasOne
    {
        return $this->hasOne(OrderQuotation::class)
            ->where('order_quotations.check_status', '=', CheckStatus::Accept->value)
            ->where('win', '=', 1);
    }

    public function wusun(): BelongsTo
    {
        return $this->belongsTo(Company::class, 'wusun_company_id', 'id');
    }

    public function check_wusun(): BelongsTo
    {
        return $this->belongsTo(Company::class, 'check_wusun_company_id', 'id');
    }

    public function repair_plan(): HasOne
    {
        return $this->hasOne(OrderRepairPlan::class, 'order_id', 'id');
    }

    public function insurers(): HasMany
    {
        return $this->hasMany(OrderInsurer::class);
    }
}
