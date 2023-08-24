<?php

namespace App\Http\Controllers;

use App\Models\Company;
use App\Models\OrderRepairPlan;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;

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
            'plan_type',
            'repair_type',
            'repair_days',
            'repair_company_id',
            'repair_company_name',
            'repair_user_id',
            'repair_user_name',
            'repair_cost',
            'cost_tables',
            'plan_text',
            'create_user_id',
            'check_status',
            'checked_at',
        ]), [
            'company_id' => $request->user()->company_id,
            'create_user_id' => $request->user()->id
        ]));


        $repair_company_id = [];
        if($plan->repair_type == OrderRepairPlan::TYPE_THIRD_REPAIR) {
           $repair_company_id[] = $plan->repair_company_id;
        }

        if ($request->input('costs')) {
            $plan->costs()->delete();
            $plan->costs()->createMany($request->input('costs'));
        }

        if ($tasks = $request->input('tasks')) {
            $plan->tasks()->delete();
            $plan->tasks()->createMany($tasks);
            foreach ($tasks as $task) {
                $repair_company_id[] = $task['repair_company_id'];
            }
        }

        if ($plan->repair_type == OrderRepairPlan::TYPE_SELF_REPAIR) {
            $plan->repair_company_id = $plan->company_id;
            $plan->repair_company_name = Company::find($plan->company_id)->name;
            $plan->save();

            $plan->order->wusun_repair_user_id = $plan->repair_user_id;
            $plan->order->repair_company_ids = count($repair_company_id)
                ? trim(implode(',', array_unique($repair_company_id)), ',') : null;
            $plan->order->save();
        }

        return success();
    }
}
