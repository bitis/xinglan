<?php

namespace App\Models;

use App\Models\Traits\DefaultDatetimeFormat;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ConsumerOrderDailyStats extends Model
{
    use HasFactory, DefaultDatetimeFormat;

    protected $fillable = [
        'company_id',
        'insurance_company_id',
        'order_count',
        'order_repair_count',
        'order_mediate_count',
        'order_budget_income',
        'date'
    ];

    public function insurance_company(): BelongsTo
    {
        return $this->belongsTo(Company::class, 'insurance_company_id', 'id');
    }
}
