<?php

namespace App\Http\Controllers;

use App\Models\Enumerations\OrderStatus;
use App\Services\OrderService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

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

        $result = [];

        foreach (OrderStatus::toArray() as $item) {
            $collect = $params->merge(['order_status' => $item['id']->value]);
            $result[$item['id']->name] = OrderService::list($request->user(), $collect)->count();
        }

        return success($result);
    }
}
