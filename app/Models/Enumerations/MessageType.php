<?php

namespace App\Models\Enumerations;

use App\Models\Enumerations\Traits\EnumArray;

enum MessageType : int
{

    use EnumArray;

    case NewOrder = 1;
    case NewCheckTask = 2;
    case ConfirmedPrice = 3;
    case OrderClosed = 4;
    case OrderDispatch = 5;
    case ConfirmedCost = 6;
    case BidQuotation = 7;

    public function name(): string
    {
        return match ($this) {
            MessageType::NewOrder => '新增订单',
            MessageType::NewCheckTask => '分派查勘任务',
            MessageType::ConfirmedPrice => '客户核价',
            MessageType::OrderClosed => '工单关闭',
            MessageType::OrderDispatch => '中标通知',
            MessageType::ConfirmedCost => '成本核算',
            MessageType::BidQuotation => '对外报价',
        };
    }

}
