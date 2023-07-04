<?php

namespace App\Models;

use App\Models\Traits\DefaultDatetimeFormat;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Message extends Model
{
    use HasFactory, DefaultDatetimeFormat;

    protected $fillable = [
        'send_company_id',
        'to_company_id',
        'type',
        'order_id',
        'order_number',
        'case_number',
        'goods_types',
        'remark',
        'accept_user_id',
        'accept_at',
        'status',
    ];

    public function sendCompany(): HasOne
    {
        return $this->hasOne(Company::class, 'id', 'send_company_id');
    }
}
