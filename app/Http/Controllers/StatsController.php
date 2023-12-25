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

        /**
         * 根据案件状态统计
         *
         * @param Request $request
         * @return JsonResponse
         */
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

            $current_company = $user->company;

            $second = Company::where('parent_id', $current_company->id)->select('id', 'name')->get()->toArray();
            $second_id = [];
            $three = [];
            $three_id = [];
            if (!empty($second)) {
                $second_id = array_column($second, 'id');
                $three = Company::whereIn('parent_id', $second_id)->select('id', 'name')->get()->toArray();
                if (!empty($three)) $three_id = array_column($three, 'id');
            }

            $group = array_merge([$current_company->id], $second_id, $three_id);

            $companies = array_merge([['id' => $current_company->id, 'name' => $current_company->name]], $second, $three);

            foreach ($order_status as $item) {
                $collect = $params->merge(['order_status' => $item->value]);
                $result[$item->name] = OrderService::list($request->user(), $collect, [], $group)
                    ->without('lossPersons')
                    ->groupBy('wusun_company_id')
                    ->selectRaw('wusun_company_id, count(*) as aggregate')->get()->toArray();
            }

            $result['all'] = OrderService::list($request->user(), $params, [], $group)
                ->without('lossPersons')
                ->groupBy('wusun_company_id')
                ->selectRaw('wusun_company_id, count(*) as aggregate')->get()->toArray();

            foreach ($companies as &$company) {
                $company['all'] = 0;
                foreach ($order_status as $status) $company[$status->name] = 0;

                foreach ($result as $key => $value) {
                    foreach ($value as $_item) {
                        if ($_item['wusun_company_id'] == $company['id']) {
                            $company[$key] = $_item['aggregate'];
                            break;
                        }
                    }
                }
            }

            return success($companies);
        }


        public function cost(Request $request): JsonResponse
        {
            $user = $request->user();

            $current_company = $user->company;

            $result = Order::with('wusun:id,name')
                ->without('lossPersons')
                ->where(function ($query) use ($request, $current_company) {
                    if ($request->input('company_id')) {
                        return $query->where('wusun_company_id', $request->input('company_id'));
                    }
                    return $query->whereIn('wusun_company_id', Company::getGroupId($current_company->id));
                })
                ->when($start_at = $request->input('start_at'), function ($query) use ($start_at) {
                    return $query->whereDate('created_at', '>=', $start_at);
                })
                ->when($end_at = $request->input('end_at'), function ($query) use ($end_at) {
                    return $query->whereDate('created_at', '<', $end_at . ' 23:59:59');
                })
                ->selectRaw(
                    'wusun_company_id,'
                    . 'sum(receivable_count) as receivable_total,' // 预算收入
                    . 'sum(total_cost) as cost_total,' // 预算总成本
                    . 'sum(paid_amount) as paid_total,' // 已付款
                    . 'sum(received_amount) as received_total,' // 已收款金额
                    . 'sum(invoiced_amount) as invoiced_total' // 已开票金额
                )
                ->groupBy('wusun_company_id')->get();

            return success($result);
        }

        /**
         * 客户案件收入成本统计
         *
         * @param Request $request
         * @return JsonResponse
         */
        public function consumerCost(Request $request): JsonResponse
        {
            $user = $request->user();

            $current_company = $user->company;

            $result = Order::with('company:id,name')
                ->without('lossPersons')
                ->where(function ($query) use ($request, $current_company) {
                    $query->where('wusun_company_id', $current_company->id);
                    if ($request->input('insurance_company_id')) {
                        return $query->where('insurance_company_id', $request->input('insurance_company_id'));
                    }
                })
                ->when($start_at = $request->input('start_at'), function ($query) use ($start_at) {
                    return $query->whereDate('created_at', '>=', $start_at);
                })
                ->when($end_at = $request->input('end_at'), function ($query) use ($end_at) {
                    return $query->whereDate('created_at', '<', $end_at . ' 23:59:59');
                })
                ->selectRaw(
                    'insurance_company_id,'
                    . 'sum(receivable_count) as receivable_total,' // 预算收入
                    . 'sum(total_cost) as cost_total,' // 预算总成本
                    . 'sum(paid_amount) as paid_total,' // 已付款
                    . 'sum(received_amount) as received_total,' // 已收款金额
                    . 'sum(invoiced_amount) as invoiced_total' // 已开票金额
                )
                ->groupBy('insurance_company_id')->get();

            return success($result);
        }
    }
