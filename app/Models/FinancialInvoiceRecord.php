<?php

namespace App\Models;

use App\Models\Traits\DefaultDatetimeFormat;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class FinancialInvoiceRecord extends Model
{
    use HasFactory, DefaultDatetimeFormat;

    protected $fillable = [
        'financial_order_id',
        'financial_type',
        'invoice_company_name',
        'company_id',
        'company_name',
        'order_id',
        'order_number',
        'customer_id',
        'customer_name',
        'paid_amount',
        'payment_time',
        'invoice_type',
        'invoice_tax_rate',
        'invoice_tax_amount',
        'invoice_number',
        'invoice_amount',
        'invoice_time',
        'bank_account_id',
        'proof',
        'case_number',
        'license_plate',
        'payment_status',
        'invoice_status',
        'invoice_operator_id',
        'invoice_operator_name',
        'payment_operator_id',
        'payment_operator_name',
        'express_company_name',
        'express_order_number',
        'express_operater_id',
        'express_operater_name',
        'express_time',
        'payment_remark',
        'invoice_remark',
    ];

    protected $casts = [
        'payment_images' => 'array',
        'invoice_images' => 'array',
    ];
}
