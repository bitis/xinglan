<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ApprovalOrderProcess extends Model
{
    use HasFactory;

    protected $fillable = [
        'approval_order_id',
        'company_id',
        'user_id',
        'step',
        'approval_status',
        'remark',
        'completed_at',
        'hidden',
    ];
}
