<?php

namespace App\Models;

use App\Models\Traits\DefaultDatetimeFormat;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ApprovalLog extends Model
{
    use HasFactory, DefaultDatetimeFormat;

    protected $fillable = [
        'order_id',
        'type',
        'status',
        'remark',
        'user_id',
        'user_name'
    ];
}
