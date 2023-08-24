<?php

namespace App\Models;

use App\Models\Traits\DefaultDatetimeFormat;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class FinancialPaymentRecord extends Model
{
    use HasFactory, DefaultDatetimeFormat;

    protected $fillable = [
        'invoice_record_id',
        'financial_order_id',
        'company_id',
        'company_name',
        'customer_id',
        'customer_name',
        'opposite_company_id',
        'opposite_company_name',
        'order_id',
        'order_number',
        'bank_account_id',
        'bank_name',
        'bank_account_number',
        'amount',
        'invoice_type',
        'invoice_number',
        'invoice_amount',
        'invoice_company_id',
        'invoice_company_name',
        'operator_id',
        'operator_name',
        'remark',
    ];
}
