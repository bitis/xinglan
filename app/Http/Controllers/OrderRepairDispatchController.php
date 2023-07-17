<?php

namespace App\Http\Controllers;

use App\Models\OrderRepairPlan;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class OrderRepairDispatchController extends Controller
{
    /**
     * 获取施工计划
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function detail(Request $request): JsonResponse
    {
        $order_id = $request->input('order_id');

        $plan = OrderRepairPlan::with(['company:id,name', 'costs', 'tasks'])->where('order_id', $order_id)->first();

        return success($plan);
    }

    /**
     * 创建、修改施工方案
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function form(Request $request): JsonResponse
    {
       $plan = OrderRepairPlan::updateOrCreate([
           'id' => $request->input('id'),
            'order_id' => $request->input('order_id')
        ], array_merge($request->only([
            'company_id',
            'plan_type',
            'repair_type',
            'repair_days',
            'repair_company_id',
            'repair_company_name',
            'repair_user_id',
            'repair_cost',
            'cost_tables',
            'plan_text',
            'create_user_id',
            'check_status',
            'checked_at',
        ]), [
            'create_user_id' => $request->user()->id
       ]));

       if ($request->input('costs')) {
           $plan->costs()->delete();
           $plan->costs()->createMany($request->input('costs'));
       }

       if ($request->input('tasks')) {
           $plan->tasks()->delete();
           $plan->tasks()->createMany($request->input('tasks'));
       }

        return success();
    }
}
