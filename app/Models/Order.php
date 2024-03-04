<?php

namespace App\Models;

use App\Jobs\BidOpeningJob;
use App\Jobs\OrderDispatch;
use App\Jobs\QuotaMessageJob;
use App\Models\Traits\DefaultDatetimeFormat;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;

class Order extends Model
{
    use HasFactory, DefaultDatetimeFormat, SoftDeletes;

    protected $fillable = [
        'order_number',
        'insurance_company_id',
        'insurance_company_name',
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
        'with_quotation',
        'bid_remark',
        'bid_status',
        'bid_end_time',
        'bid_win_price',
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
        'close_status',
        'repair_company_ids',
        'repair_cost',
        'labor_costs',
        'material_cost',
        'other_cost',
        'total_cost',
        'cost_remark',
        'cost_check_status',
        'cost_submit_at',
        'cost_creator_id',
        'cost_creator_name',
        'cost_checked_at',
        'repair_bid_publish_at',
        'is_direct'
    ];

    protected $casts = [
        'images' => 'array',
        'review_images' => 'array'
    ];

    protected $with = ['lossPersons'];

    const BID_STATUS_PROGRESSING = 0;
    const BID_STATUS_FINISHED = 1;

    const BID_TYPE_JINGJIA = 1; // 竞价
    const BID_TYPE_FENPAI = 2; // 不经竞价、直接分派

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

    const REPAIR_STATUS_WAIT = 0;
    const REPAIR_STATUS_REPAIRING = 1;
    const REPAIR_STATUS_FINISHED = 2;


    const QUOTE_STATUS_WAIT = 0; // 未报价
    const QUOTE_STATUS_APPROVAL = 1; // 审核中
    const QUOTE_STATUS_FINISHED = 2; // 已报价

    const CONFIRM_PRICE_STATUS_WAIT = 0; // 未核价
    const CONFIRM_PRICE_STATUS_APPROVAL = 1; // 审核中
    const CONFIRM_PRICE_STATUS_FINISHED = 2; // 已核价

    const PLAN_TYPE_REPAIR = 1; // 施工修复
    const PLAN_TYPE_MEDIATE = 2; // 协调处理


    const COST_CHECK_STATUS_WAIT = 0; // 未提交
    const COST_CHECK_STATUS_APPROVAL = 1; // 审核中
    const COST_CHECK_STATUS_PASS = 2; // 审核通过

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class, 'insurance_company_id', 'id');
    }

    public static function genOrderNumber(): string
    {
        return 'XL' . date('ymdHis') . rand(10, 99);
    }

    public function isBidOrder(): bool
    {
        return $this->bid_type == 1;
    }

    public function quotations(): HasMany
    {
        return $this->hasMany(OrderQuotation::class);
    }

    public function quotation(): HasOne
    {
        return $this->hasOne(OrderQuotation::class);
    }

    public function pure_quotation(): HasOne
    {
        return $this->hasOne(OrderQuotation::class)->where('win', 1)->without('items');
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

    public function repair_quotas(): HasMany
    {
        return $this->hasMany(RepairQuota::class, 'order_id', 'id');
    }

    public function lossPersons(): HasMany
    {
        return $this->hasMany(LossPerson::class, 'order_id', 'id');
    }

    /**
     * 工单收款、支付记录
     *
     * @return HasMany
     */
    public function payment_records(): HasMany
    {
        return $this->hasMany(FinancialPaymentRecord::class, 'order_id', 'id');
    }
}
