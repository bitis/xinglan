<?php

namespace App\Models;

use App\Models\Traits\DefaultDatetimeFormat;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Order extends Model
{
    use HasFactory, DefaultDatetimeFormat;

    protected $fillable = [
        'company_id',
        'order_number',
        'external_number',
        'case_number',
        'insurance_check_name',
        'insurance_check_phone',
        'post_time',
        'insurance_type',
        'license_plate',
        'vin',
        'locations',
        'province',
        'city',
        'area',
        'address',
        'creator_id',
        'creator_name',
        'insurance_people',
        'insurance_phone',
        'driver_name',
        'driver_phone',
        'remark',
        'customer_remark',
        'order_status',
        'close_status',
        'goods_types',
        'goods_name',
        'owner_name',
        'owner_phone',
        'owner_price',
        'images',
        'goods_remark'
    ];

    protected $casts = [
        'images' => 'array'
    ];

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class, 'company_id', 'id');
    }
}
