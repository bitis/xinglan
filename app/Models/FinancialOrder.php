<?php

namespace App\Models;

use App\Models\Traits\DefaultDatetimeFormat;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Arr;

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
        'payment_name',
        'payment_bank',
        'payment_account',
        'apply_payment_reason',
        'apply_payment_images',
        'wusun_check_id',
        'wusun_check_name',
        'order_number',
        'order_id',
        'type',
        'baoxiao',
        'opposite_company_id',
        'opposite_company_name',
        'payment_name',
        'payment_bank',
        'payment_account',
        'apply_payment_reason',
        'apply_payment_images',
        'total_amount',
        'paid_amount',
        'invoiced_amount',
        'payment_status',
        'invoice_status',
        'paid_at',
        'check_status',
        'checked_at',
    ];

    const TYPE_RECEIPT = 1; // 收
    const TYPE_PAYMENT = 2; // 付

    const STATUS_WAIT = 1; // 待..
    const STATUS_PART = 2; // 部分..
    const STATUS_DONE = 3; // 已..

    protected $casts = [
        'apply_payment_images' => 'array'
    ];

    public static function findAndGetAttrs(int $id, array $attrKeys = []): array
    {
        return Arr::only(static::find($id)->toArray(), $attrKeys);
    }

    /**
     * 根据工单创建记录
     *
     * @param Order $order
     * @param array $append
     * @return FinancialOrder
     */
    public static function createByOrder(Order $order, $append = []): FinancialOrder
    {
        $financialOrder = static::create(array_merge([
            'company_id' => $order->wusun_company_id,
            'company_name' => $order->wusun_company_name,
            'insurance_company_id' => $order->insurance_company_id,
            'insurance_company_name' => $order->insurance_company_name,
            'case_number' => $order->case_number,
            'province' => $order->province,
            'city' => $order->city,
            'area' => $order->area,
            'address' => $order->address,
            'post_time' => $order->post_time,
            'license_plate' => $order->license_plate,
            'vin' => $order->vin,
            'insurance_check_phone' => $order->insurance_check_phone,
            'insurance_check_name' => $order->insurance_check_name,
            'wusun_check_id' => $order->wusun_check_id,
            'wusun_check_name' => $order->wusun_check_name,
            'order_number' => $order->order_number,
            'order_id' => $order->id,
        ], $append));

        $financialOrder->save();

        return $financialOrder;
    }
}
