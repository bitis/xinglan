<?php

namespace App\Models;

use App\Models\Traits\DefaultDatetimeFormat;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BidOption extends Model
{
    use HasFactory, DefaultDatetimeFormat;

    protected $fillable = [
        'company_id',
        'min_goods_price',
        'mid_goods_price',
        'working_time_deadline_min',
        'resting_time_deadline_min',
        'working_time_deadline_mid',
        'resting_time_deadline_mid',
        'working_time_deadline_max',
        'resting_time_deadline_max',
        'status'
    ];

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class, 'company_id', 'id');
    }
}
