<?php

namespace App\Http\Controllers;

use App\Models\Order;
use App\Models\OrderRepairPlan;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class OrderRepairController extends Controller
{
    public function detail(Request $request): JsonResponse
    {
        $order_id = $request->input('order_id');

        $plan = OrderRepairPlan::with('tasks')->where('order_id', $order_id)->first();

        return success($plan);
    }

    public function form(Request $request): JsonResponse
    {
        $order_id = $request->input('order_id');

        $order = Order::find($order_id);

        $plan = OrderRepairPlan::where('order_id', $order_id)->first();

        $plan->fill($request->only([
            'repair_status',
            'repair_start_at',
            'repair_end_at',
            'cost_images',
            'before_repair_images',
            'repair_images',
            'after_repair_images',
            'repair_remark'
        ]));

        $plan->save();

        $order->repair_status = $plan->repair_status;
        $order->save();

        return success();
    }

    /**
     * 回退施工状态
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function rollback(Request $request): JsonResponse
    {
        $order_id = $request->input('order_id');
        $order = Order::find($order_id);
        $plan = OrderRepairPlan::with('tasks')->where('order_id', $order_id)->first();

        if ($plan->repair_status > OrderRepairPlan::REPAIR_STATUS_WAIT) {
            $plan->repair_status -= 1;
            $plan->save();
        } else {
            $plan->delete();
            $order->repair_status = Order::REPAIR_STATUS_WAIT;
            $order->save();
        }

        return success();
    }
}
