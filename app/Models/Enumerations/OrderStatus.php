<?php

namespace App\Models\Enumerations;

use App\Models\Enumerations\Traits\EnumArray;
use App\Models\Order;
use App\Models\OrderQuotation;
use Illuminate\Database\Eloquent\Builder;

enum OrderStatus: int
{

    use EnumArray;

    case WaitCheck = 1;
    case Checking = 2;
    case WaitPlan = 3;
    case WaitCost = 4;
    case WaitQuote = 5;
    case WaitConfirmPrice = 6;
    case WaitRepair = 7;
    case Repairing = 8;
    case Repaired = 9;
    case Closed = 10;
    case mediate = 99;


    public function name(): string
    {
        return match ($this) {
            OrderStatus::WaitCheck => '待查勘',
            OrderStatus::Checking => '查勘中',
            OrderStatus::WaitPlan => '待确认方案',
            OrderStatus::WaitCost => '待成本核算',
            OrderStatus::WaitQuote => '待对外造价',
            OrderStatus::WaitConfirmPrice => '未核价',
            OrderStatus::WaitRepair => '待施工',
            OrderStatus::Repairing => '施工中',
            OrderStatus::Repaired => '已修复未结案',
            OrderStatus::Closed => '已结案',
            OrderStatus::mediate => '协调处理',
        };
    }

    public function filter(Builder $query)
    {
        return match ($this) {
            OrderStatus::WaitCheck => $query->where('wusun_check_status', Order::WUSUN_CHECK_STATUS_WAITING),
            OrderStatus::Checking => $query->where('wusun_check_status', Order::WUSUN_CHECK_STATUS_CHECKING),
            OrderStatus::WaitPlan => $query->where('wusun_check_status', Order::WUSUN_CHECK_STATUS_FINISHED)
                ->whereNull('plan_confirm_at'),
            OrderStatus::WaitCost => $query->leftJoin('order_quotations', 'order_quotations.order_id', '=', 'orders.id')
                ->where(function ($query) {
                    $query->where('order_quotations.check_status', CheckStatus::Accept->value)->orWhereNull('order_quotations.id');
                }),
            OrderStatus::WaitQuote,
            OrderStatus::WaitConfirmPrice => $query->where('confirm_price_status', Order::CONFIRM_PRICE_STATUS_WAIT)
                ->where('plan_type', Order::PLAN_TYPE_MEDIATE),
            OrderStatus::WaitRepair => $query->where('repair_status', Order::REPAIR_STATUS_WAIT)
                ->where('plan_type', Order::PLAN_TYPE_MEDIATE),
            OrderStatus::Repairing => $query->where('repair_status', Order::REPAIR_STATUS_REPAIRING),
            OrderStatus::Repaired => $query->where('repair_status', Order::REPAIR_STATUS_FINISHED)
                ->where('close_status', OrderCloseStatus::Wait->value),
            OrderStatus::Closed => $query->where('close_status', OrderCloseStatus::Closed->value),
            OrderStatus::mediate => $query->where('plan_type', Order::PLAN_TYPE_MEDIATE),
        };
    }
}
