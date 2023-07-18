<?php

namespace App\Http\Controllers;

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

        return success();
    }
}
