<?php

namespace App\Http\Controllers;

use App\Models\Order;
use App\Models\OrderRepairPlan;
use App\Models\RepairQuota;
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

    /**
     * 维修
     * @param Request $request
     * @return JsonResponse
     */
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

        if (!$plan) return success();

        if ($plan->repair_status > OrderRepairPlan::REPAIR_STATUS_WAIT) {
            $plan->repair_status -= 1;
            $plan->save();
            $order->repair_status = $plan->repair_status;
            $order->save();
        } else {
            $plan->delete();
            $order->repair_status = Order::REPAIR_STATUS_WAIT;
            $order->repair_company_ids = '';
            $order->save();

            RepairQuota::where('order_id', $order->id)->where('win', 1)->update([
                'win' => 0,
                'updated_at' => now()->toDateTimeString()
            ]);

            RepairQuota::where('order_id', $order->id)->where('quota_type', RepairQuota::TYPE_CHOOSE)->delete();
        }

        return success();
    }
}
