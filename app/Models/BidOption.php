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
        'status',
        'auto'
    ];

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class, 'company_id', 'id');
    }

    public static function findByCompany($id)
    {
        return self::where('company_id', $id)->where('status', Status::Normal->value)->first();
    }

    /**
     * 竞价截止时间
     *
     * @param $order
     * @param $bidOption
     * @return string
     */
    public static function getBidEndTime($order, $bidOption): string
    {
        $now = date('His');

        if (empty($bidOption)) return now()->addHours(12)->toDateTimeString();

        if ($order->owner_price < $bidOption->min_goods_price) {
            if ($now > '083000' && $now < '180000') $duration = $bidOption->working_time_deadline_min;
            else $duration = $bidOption->resting_time_deadline_min;
        } elseif ($order->owner_price < $bidOption->mid_goods_price) {
            if ($now > '083000' && $now < '180000') $duration = $bidOption->working_time_deadline_mid;
            else $duration = $bidOption->resting_time_deadline_mid;
        } else {
            if ($now > '083000' && $now < '180000') $duration = $bidOption->working_time_deadline_max;
            else $duration = $bidOption->resting_time_deadline_max;
        }

        $hours = ceil($duration);
        $minutes = $duration * 60 % 60;

        return now()->addHours($hours)->addMinutes($minutes)->toDateTimeString();
    }
}
