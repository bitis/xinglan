<?php

namespace App\Http\Controllers;

use App\Models\Enumerations\CompanyType;
use App\Models\Enumerations\OrderStatus;
use App\Services\OrderService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;

class IndexController extends Controller
{
    /**
     * APP 首页宫格数据
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function index(Request $request): JsonResponse
    {
        $params = $request->collect();

        $user = $request->user();

        $company = $user->company;

        $result = [];

        $order_status = OrderStatus::toArray();

        if ($company->getRawOriginal('type') == CompanyType::WeiXiu->value) {
            $order_status = Arr::only($order_status, [
                OrderStatus::WaitRepair->value,
                OrderStatus::Repairing->value,
                OrderStatus::Repaired->value,
                OrderStatus::Paid->value,
            ]);
        }

        foreach ($order_status as $item) {
            $collect = $params->merge(['order_status' => $item['id']->value]);
            $result[$item['id']->name] = OrderService::list($request->user(), $collect)->count();
        }

        $result['all'] = OrderService::list($request->user(), $params)->count();
        return success($result);
    }
}
