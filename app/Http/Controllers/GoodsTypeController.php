<?php

namespace App\Http\Controllers;

use App\Http\Requests\GoodsTypeRequest;
use App\Models\GoodsType;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class GoodsTypeController extends Controller
{
    /**
     * 物损类型列表
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function index(Request $request): JsonResponse
    {
        $result = GoodsType::where('type', $request->input('type', 0))
            ->whereIn('company_id', [$request->user()->company_id, 0])
            ->paginate(getPerPage());

        return success($result);
    }

    /**
     * 新增、修改受损类型
     *
     * @param GoodsTypeRequest $request
     * @return JsonResponse
     */
    public function form(GoodsTypeRequest $request): JsonResponse
    {
        $goodsType = GoodsType::where('company_id', $request->user()->company_id)
            ->findOr($request->input('id'), fn() => new GoodsType(['company_id' => $request->user()->company_id]));

        $goodsType->fill($request->only(['name', 'remark', 'order', 'status']));

        $goodsType->save();

        return success();
    }
}
