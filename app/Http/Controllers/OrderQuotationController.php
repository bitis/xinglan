<?php

namespace App\Http\Controllers;

use App\Models\Enumerations\CheckStatus;
use App\Models\Order;
use App\Models\OrderQuotation;
use Illuminate\Contracts\View\View;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Ramsey\Uuid\Exception\TimeSourceException;

class OrderQuotationController extends Controller
{

    /**
     * 报价大厅
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function index(Request $request): JsonResponse
    {
        return success();
    }

    /**
     * 获取当前公司某工单的报价详情 （物损公司）
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function getByOrderId(Request $request): JsonResponse
    {
        $quotation = OrderQuotation::where('company_id', $request->user()->id)
            ->where('order_id', $request->input('order_id'))
            ->first();

        return success($quotation);
    }

    /**
     * 提交报价（物损公司）
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function form(Request $request): JsonResponse
    {
        $user = $request->user();

        $order = Order::find($request->input('order_id'));

        $quotation = OrderQuotation::where('company_id', $user->compnay_id)
            ->findOr($request->input('id'), fn() => new OrderQuotation(['security_code' => Str::random()]));

        $quotation->fill($request->only([
            'order_id',
            'plan_type',
            'repair_days',
            'repair_cost',
            'other_cost',
            'total_cost',
            'profit_margin',
            'profit_margin_ratio',
            'repair_remark',
            'total_price',
            'images',
            'submit'
        ]));

        $quotation->company_id = $user->company_id;

        $quotation->save();

        $quotation->items()->delete();

        $quotation->items()->createMany($request->input('items'));

        if ($quotation->submit) {
            // TODO 提交审核
        }

        return success();
    }

    /**
     * 生成报价单
     *
     * @param string $code
     * @return View
     */
    public function getBySecurityCode(string $code): View
    {
        $quotation = OrderQuotation::with(['company','order', 'order.company'])->where('security_code', $code)->first();

        return view('quota.table')
            ->with(compact('quotation'));
    }
}
