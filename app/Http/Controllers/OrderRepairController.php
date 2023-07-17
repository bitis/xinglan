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

        $company_id = $request->user()->company_id;

        $plan = OrderRepairPlan::where('order_id', $order_id)->first();

        if ($plan->repair_type == 1) { // 自修

        }

        return success();
    }

    public function form(Request $request): JsonResponse
    {
        return success();
    }
}
