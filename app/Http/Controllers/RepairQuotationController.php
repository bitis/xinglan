<?php

namespace App\Http\Controllers;

use App\Models\CompanyProvider;
use App\Models\Enumerations\CompanyType;
use App\Models\Order;
use App\Models\RepairQuota;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class RepairQuotationController extends Controller
{
    /**
     * 维修报价大厅
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function index(Request $request): JsonResponse
    {
        $user = $request->user();

        $company = $user->company;

        $orders = Order::leftjoin('repair_quotas as quota', 'orders.id', 'quota.order_id')
            ->where(function ($query) use ($company) {
                return match ($company->getRawOriginal('type')) {
                    CompanyType::WuSun->value => $query->where('wusun_company_id', $company->id),
                    CompanyType::WeiXiu->value => $query->whereIn('wusun_company_id', CompanyProvider::where('provider_id', $company->id)->pluck('company_id')),
                };
            })
            ->where('repair_bid_type', 1)
            ->orderBy('orders.id', 'desc')
            ->selectRaw('orders.*, quota.repair_company_id, quota.win, quota.submit_at as quota_submit_at')
            ->paginate(getPerPage());

        return success($orders);
    }

    /**
     * 维修方报价
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function quota(Request $request): JsonResponse
    {
        $user = $request->user();

        $company = $user->company;

        $order = Order::find($request->input('order_id'));

        if (empty($order) or $order->repair_bid_type != 1) return fail('工单不存在或不允许报价');

        $quota = RepairQuota::where('order_id', $order->id)->where('repair_company_id', $user->company_id)->first();

        if ($quota) return fail('不允许二次报价');

        RepairQuota::create([
            'order_id' => $order->id,
            'repair_company_id' => $user->company_id,
            'repair_company_name' => $company->name,
            'total_price' => $request->input('total_price'),
            'repair_days' => $request->input('repair_days'),
            'images',
            'submit_at' => now()->toDateTimeString(),
            'operator_id' => $user->id,
            'operator_name' => $user->name,
            'win' => 0,
            'quota_type' => RepairQuota::TYPE_SELF,
            'remark' => $request->input('remark'),
        ]);

        return success();
    }
}
