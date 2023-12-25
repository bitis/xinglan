<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ConsumerOrderDailyStats extends Model
{
    use HasFactory;

    protected $fillable = [
        'company_id',
        'insurance_company_id',
        'order_count',
        'order_repair_count',
        'order_mediate_count',
        'order_budget_income',
        'order_real_income',
        'date'
    ];
}
