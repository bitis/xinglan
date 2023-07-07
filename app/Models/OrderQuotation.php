<?php

namespace App\Models;

use App\Jobs\OrderQuotationQrcodeJob;
use App\Models\Traits\DefaultDatetimeFormat;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class OrderQuotation extends Model
{
    use HasFactory, DefaultDatetimeFormat;

    protected $with = ['items'];

    protected $fillable = [
        'order_id',
        'company_id',
        'plan_type',
        'repair_days',
        'repair_cost',
        'other_cost',
        'total_cost',
        'profit_margin',
        'profit_margin_ratio',
        'repair_remark',
        'total_price',
        'images',
        'submit',
        'security_code',
        'pdf'
    ];

    protected $casts = [
        'images' => 'array'
    ];

    public function items(): HasMany
    {
        return $this->hasMany(QuotationItem::class);
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class, 'company_id', 'id');
    }

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class, 'order_id', 'id');
    }

    protected static function booted()
    {
        static::created(function ($quotation) {
            OrderQuotationQrcodeJob::dispatch($quotation);
        });
    }
}