<?php

namespace App\Models\Enumerations;

use App\Models\Enumerations\Traits\EnumArray;
use App\Models\Order;
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
    case Paid = 11;
    case Closed = 10;
    case Mediate = 99;


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
            OrderStatus::Mediate => '协调处理',
            OrderStatus::Paid => '已付款',
        };
    }

    public function filter(Builder $query)
    {
        return match ($this) {
            OrderStatus::WaitCheck => $query
                ->where('close_status', '<>', OrderCloseStatus::Closed->value)
                ->where('wusun_check_status', Order::WUSUN_CHECK_STATUS_WAITING),
            OrderStatus::Checking => $query
                ->where('close_status', '<>', OrderCloseStatus::Closed->value)
                ->where('wusun_check_status', Order::WUSUN_CHECK_STATUS_CHECKING),
            OrderStatus::WaitPlan => $query
                ->where('close_status', '<>', OrderCloseStatus::Closed->value)
                ->where('wusun_check_status', Order::WUSUN_CHECK_STATUS_FINISHED)
                ->whereNull('plan_confirm_at'),
            OrderStatus::WaitCost => $query
                ->where('close_status', '<>', OrderCloseStatus::Closed->value)
                ->leftJoin('order_quotations', 'order_quotations.order_id', '=', 'orders.id')
                ->where('order_quotations.check_status', '=', CheckStatus::Accept->value)
                ->where('cost_check_status', '<>', Order::COST_CHECK_STATUS_PASS)
                ->where('confirm_price_status', '=', Order::CONFIRM_PRICE_STATUS_FINISHED),
            OrderStatus::WaitQuote => $query
                ->where('close_status', '<>', OrderCloseStatus::Closed->value)
                ->leftJoin('order_quotations', 'order_quotations.order_id', '=', 'orders.id')
                ->where('close_status', '<>', OrderCloseStatus::Closed->value)
                ->where('orders.plan_type', Order::PLAN_TYPE_REPAIR)
                ->where(function ($query) {
                    $query->whereNotNull('plan_confirm_at')->where(function ($query) {
                        $query->where('order_quotations.check_status', '<>', CheckStatus::Accept->value)
                            ->orWhereNull('order_quotations.id');
                    });
                }),
            OrderStatus::WaitConfirmPrice => $query
                ->where('close_status', '<>', OrderCloseStatus::Closed->value)
                ->leftJoin('order_quotations', 'order_quotations.order_id', '=', 'orders.id')
                ->where('order_quotations.check_status', CheckStatus::Accept->value)
                ->where('orders.plan_type', Order::PLAN_TYPE_REPAIR)
                ->where('confirm_price_status', '<>', Order::CONFIRM_PRICE_STATUS_FINISHED),
            OrderStatus::WaitRepair => $query
                ->where('close_status', '<>', OrderCloseStatus::Closed->value)
                ->where('confirm_price_status', Order::CONFIRM_PRICE_STATUS_FINISHED)
                ->where('repair_status', Order::REPAIR_STATUS_WAIT)
                ->where('orders.plan_type', Order::PLAN_TYPE_REPAIR),
            OrderStatus::Repairing => $query
                ->where('close_status', '<>', OrderCloseStatus::Closed->value)
                ->where('repair_status', Order::REPAIR_STATUS_REPAIRING),
            OrderStatus::Repaired => $query
                ->where('close_status', '<>', OrderCloseStatus::Closed->value)
                ->where('repair_status', Order::REPAIR_STATUS_FINISHED),
            OrderStatus::Closed => $query->where('close_status', OrderCloseStatus::Closed->value),
            OrderStatus::Paid => $query->where('paid_status', Status::Normal),
            OrderStatus::Mediate => $query->where('close_status', '<>', OrderCloseStatus::Closed->value)
                ->where('orders.plan_type', Order::PLAN_TYPE_MEDIATE),
        };
    }
}
