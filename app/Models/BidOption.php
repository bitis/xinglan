<?php

namespace App\Models;

use App\Models\Enumerations\Status;
use App\Models\Traits\DefaultDatetimeFormat;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BidOption extends Model
{
    use HasFactory, DefaultDatetimeFormat;

    protected $fillable = [
        'company_id',
        'bid_first_price',
        'min_goods_price',
        'mid_goods_price',
        'working_time_deadline_min',
        'resting_time_deadline_min',
        'working_time_deadline_mid',
        'resting_time_deadline_mid',
        'working_time_deadline_max',
        'resting_time_deadline_max',
        'order_dispatch_role',
        'status'
    ];

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class, 'company_id', 'id');
    }

    public static function findByCompany($id)
    {
        return self::where('company_id', $id)->where('status', Status::Normal->value)->first();
    }
}
