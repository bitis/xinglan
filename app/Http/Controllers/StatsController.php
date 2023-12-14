<?php

namespace App\Http\Controllers;

use App\Models\Company;
use App\Models\Enumerations\CompanyType;
use App\Models\Enumerations\OrderStatus;
use App\Models\Order;
use App\Services\OrderService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class StatsController extends Controller
{
    /**
     * 统计
     * @param Request $request
     * @return JsonResponse
     */
    public function areaCase(Request $request): JsonResponse
    {
        $user = $request->user();

        $company = $user->company;

        Order::when(
            $request->input('company_id'),
            function ($query, $company_id) use ($company) {
                return match ($company->getRawOriginal('type')) {
                    CompanyType::BaoXian->value => $query->where('insurance_company_id', $company_id),
                    CompanyType::WuSun->value => $query->where('wusun_company_id', $company_id),
                };
            },
            function ($query) use ($company) {
                $group = Company::getGroupId($company->id);
                return match ($company->getRawOriginal('type')) {
                    CompanyType::BaoXian->value => $query->whereIn('insurance_company_id', $group),
                    CompanyType::WuSun->value => $query->whereIn('wusun_company_id', $group),
                };
            });

        return success();
    }

    public function byStatus(Request $request): JsonResponse
    {
        $params = $request->collect();

        $result = [];

        $order_status = [
            OrderStatus::WaitCheck,
            OrderStatus::Checking,
            OrderStatus::WaitPlan,
//            OrderStatus::WaitCost => '待成本核算',
//            OrderStatus::WaitQuote => '待对外造价',
//            OrderStatus::WaitConfirmPrice => '未核价',
            OrderStatus::WaitRepair,
            OrderStatus::Repairing,
            OrderStatus::Repaired,
            OrderStatus::Closed,
            OrderStatus::Closing,
            OrderStatus::Mediate,
//            OrderStatus::Paid => '已付款',
        ];

        $user = $request->user();

        $company = $user->company;

        $second = Company::where('parent_id', $company->id)->select('id', 'name')->get()->toArray();
        $three = Company::whereIn('parent_id', $second)->select('id', 'name')->get()->toArray();

        $group = array_merge([$company->id], array_column($second, 'id'), array_column($three, 'id'));

        foreach ($order_status as $item) {
            $collect = $params->merge(['order_status' => $item->value]);
            $result[$item->name] = OrderService::list($request->user(), $collect, [], $group)
                ->without('lossPersons')
                ->groupBy('wusun_company_id')
                ->selectRaw('wusun_company_id, count(*) as aggregate')->get();
        }

        $result['all'] = OrderService::list($request->user(), $params, [], $group)
            ->without('lossPersons')
            ->groupBy('wusun_company_id')
            ->selectRaw('wusun_company_id, count(*) as aggregate')->get();

        return success($result);
    }
}
