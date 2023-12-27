<?php

namespace App\Models;

use App\Models\Traits\DefaultDatetimeFormat;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class FinancialPaymentRecord extends Model
{
    use HasFactory, DefaultDatetimeFormat;

    protected $fillable = [
        'invoice_record_id',
        'financial_order_id',
        'financial_type',
        'company_id',
        'company_name',
        'customer_id',
        'customer_name',
        'opposite_company_id',
        'opposite_company_name',
        'order_id',
        'order_post_time',
        'order_number',
        'case_number',
        'license_plate',
        'bank_account_id',
        'bank_name',
        'bank_account_number',
        'his_name',
        'his_bank_name',
        'his_bank_number',
        'amount',
        'invoice_id',
        'invoice_type',
        'invoice_number',
        'invoice_amount',
        'invoice_company_id',
        'invoice_company_name',
        'invoice_created_at',
        'baoxiao',
        'operator_id',
        'operator_name',
        'payment_time',
        'remark',
        'payment_images'
    ];

    protected $casts = [
        'payment_images' => 'array'
    ];

    public function financialOrder(): BelongsTo
    {
        return $this->belongsTo(FinancialOrder::class);
    }

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class)->without('lossPersons');
    }
}
