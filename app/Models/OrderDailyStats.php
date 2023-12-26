<?php

namespace App\Models;

use App\Models\Traits\DefaultDatetimeFormat;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class OrderDailyStats extends Model
{
    use HasFactory, DefaultDatetimeFormat;

    protected $fillable = [
        'company_id',
        'parent_id',
        'order_count',
        'order_repair_count',
        'order_mediate_count',
        'date',
    ];

    protected $with = ['company:id,name', 'children'];

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class, 'company_id', 'id');
    }

    public function children()
    {
        return $this->hasMany(OrderDailyStats::class, 'parent_id', 'company_id');
    }
}
