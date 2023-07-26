<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class OrderLog extends Model
{
    use HasFactory;

    protected $fillable = [
        'order_id',
        'type',
        'creator_id',
        'creator_name',
        'creator_company_id',
        'creator_company_name',
        'remark',
        'content',
        'platform'
    ];

    const TYPE_NEW_ORDER = 1; // 创建工单
    const TYPE_DISPATCH_CHECK = 2; // 分配查勘

    const TYPE_QUOTATION = 3; // 报价
    const TYPE_SUBMIT_QUOTATION = 4; // 提交报价审核
    const TYPE_APPROVAL = 5; // 审核

}
