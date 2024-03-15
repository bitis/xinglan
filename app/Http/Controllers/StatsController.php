<?php

namespace App\Http\Controllers;

use App\Models\Company;
use App\Models\ConsumerOrderDailyStats;
use App\Models\Enumerations\CompanyType;
use App\Models\Enumerations\OrderStatus;
use App\Models\Order;
use App\Models\OrderDailyStats;
use App\Services\OrderService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;

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
     * 案件数量统计
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function caseStats(Request $request): JsonResponse
    {
        $user = $request->user();

        $company_id = $request->input('company_id') ?? $user->company_id;

        $company = Company::find($company_id);

        $group = Company::getGroupId($company_id);

        $start_at = $request->input('start_at') ?? now()->firstOfMonth()->toDateString();
        $end_at = $request->input('end_at') ?? now()->toDateString();

        $result = OrderDailyStats::with('company:id,name')
            ->whereDate('date', '>=', $start_at)
            ->whereDate('date', '<=', $end_at)
            ->whereIn('company_id', $group)
            ->select(['company_id', 'parent_id', 'order_count', 'order_repair_count', 'order_mediate_count', 'date'])
            ->get()->toArray();

        $firstItem = [
            'company_id' => $company->id,
            'name' => $company->name,
            'order_count' => 0,
            'order_repair_count' => 0,
            'order_mediate_count' => 0,
            'children' => [],
            'stats' => [],
        ];

        foreach ($result as $item) {
            if ($item['company_id'] == $firstItem['company_id']) {
                $firstItem['order_count'] += $item['order_count'];
                $firstItem['order_repair_count'] += $item['order_repair_count'];
                $firstItem['order_mediate_count'] += $item['order_mediate_count'];
                $firstItem['stats'][] = Arr::only($item, ['date', 'order_count', 'order_repair_count', 'order_mediate_count']);
            }

            if ($item['parent_id'] == $firstItem['company_id']) {
                if (!isset($firstItem['children'][$item['company_id']]))
                    $firstItem['children'][$item['company_id']] = [
                        'company_id' => $item['company']['id'],
                        'name' => $item['company']['name'],
                        'order_count' => 0,
                        'order_repair_count' => 0,
                        'order_mediate_count' => 0,
                        'children' => [],
                        'stats' => [],
                    ];
            }
        }

        foreach ($firstItem['children'] as &$child) {
            foreach ($result as $item) {
                if ($item['company_id'] == $child['company_id']) {
                    $child['order_count'] += $item['order_count'];
                    $child['order_repair_count'] += $item['order_repair_count'];
                    $child['order_mediate_count'] += $item['order_mediate_count'];
                    $child['stats'][] = Arr::only($item, ['date', 'order_count', 'order_repair_count', 'order_mediate_count']);
                }

                if ($item['parent_id'] == $child['company_id']) {
                    if (!isset($child['children'][$item['company_id']]))
                        $child['children'][$item['company_id']] = [
                            'company_id' => $item['company']['id'],
                            'name' => $item['company']['name'],
                            'order_count' => 0,
                            'order_repair_count' => 0,
                            'order_mediate_count' => 0,
                            'children' => [],
                            'stats' => [],
                        ];
                }
            }
        }

        foreach ($firstItem['children'] as &$children) {
            foreach ($children['children'] as &$child) {
                foreach ($result as $item) {
                    if ($item['company_id'] == $child['company_id']) {
                        $child['order_count'] += $item['order_count'];
                        $child['order_repair_count'] += $item['order_repair_count'];
                        $child['order_mediate_count'] += $item['order_mediate_count'];
                        $child['stats'][] = Arr::only($item, ['date', 'order_count', 'order_repair_count', 'order_mediate_count']);
                    }

                    if ($item['parent_id'] == $child['company_id']) {
                        if (!isset($child['children'][$item['company_id']]))
                            $child['children'][$item['company_id']] = [
                                'company_id' => $item['company']['id'],
                                'name' => $item['company']['name'],
                                'order_count' => 0,
                                'order_repair_count' => 0,
                                'order_mediate_count' => 0,
                                'children' => [],
                                'stats' => [],
                            ];
                    }
                }
            }
        }

        foreach ($firstItem['children'] as &$children) {
            $children['children'] = array_values($children['children']);
        }

        $firstItem['children'] = array_values($firstItem['children']);
        return success($firstItem);
    }


    /**
     * 客户案件统计
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function consumerCaseStats(Request $request): JsonResponse
    {
        $start_at = $request->input('start_at') ?? now()->addDays(-7)->toDateString();
        $end_at = $request->input('end_at') ?? now()->toDateString();

        $result = ConsumerOrderDailyStats::with('insurance_company:id,name')
            ->whereDate('date', '>=', $start_at)
            ->whereDate('date', '<=', $end_at)
            ->when($company_id = $request->input('company_id'), function ($query) use ($company_id) {
                $query->where('company_id', $company_id);
            })
            ->get();

        return success($result);
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

        if ($request->input('company_id'))
            $current_company = Company::find($request->input('company_id'));
        else $current_company = $user->company;

        $second = Company::where('parent_id', $current_company->id)->select('id', 'name', 'parent_id')->get()->toArray();
        $second_id = [];
        $three = [];
        $three_id = [];
        if (!empty($second)) {
            $second_id = array_column($second, 'id');
            $three = Company::whereIn('parent_id', $second_id)->select('id', 'name', 'parent_id')->get()->toArray();
            if (!empty($three)) $three_id = array_column($three, 'id');
        }

        $group = array_merge([$current_company->id], $second_id, $three_id);

        $companies = array_merge([['id' => $current_company->id, 'name' => $current_company->name, 'parent_id' => $current_company->parent_id]], $second, $three);

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

        $firstCompany = ['id' => $current_company->id, 'name' => $current_company->name, 'parent_id' => $current_company->parent_id,
            'all' => 0, 'WaitCheck' => 0, 'Checking' => 0, 'WaitPlan' => 0, 'WaitRepair' => 0, 'Repairing' => 0,
            'Repaired' => 0, 'Closed' => 0, 'Closing' => 0, 'Mediate' => 0, 'children' => []];

        foreach ($companies as &$company) {
            $company['all'] = 0;
            foreach ($order_status as $status) $company[$status->name] = 0;

            foreach ($result as $key => $value) {
                foreach ($value as $_item) {
                    if ($_item['wusun_company_id'] == $company['id']) {
                        $company[$key] = $_item['aggregate'];
                        $firstCompany[$key] += $_item['aggregate'];
                        break;
                    }
                }
            }

            if ($company['parent_id'] == $firstCompany['id']) {
                $firstCompany['children'][] = $company;
            }
        }
        foreach ($firstCompany['children'] as &$child) {
            foreach ($companies as $company) {
                $child['children'] = [];
                if ($company['parent_id'] == $child['id']) {
                    $child['children'][] = $company;
                    $child['all'] += $company['all'];
                    $child['WaitCheck'] += $company['WaitCheck'];
                    $child['Checking'] += $company['Checking'];
                    $child['WaitPlan'] += $company['WaitPlan'];
                    $child['WaitRepair'] += $company['WaitRepair'];
                    $child['Repairing'] += $company['Repairing'];
                    $child['Repaired'] += $company['Repaired'];
                    $child['Closed'] += $company['Closed'];
                    $child['Closing'] += $company['Closing'];
                    $child['Mediate'] += $company['Mediate'];
                    break;
                }
            }
        }
        return success($firstCompany);
    }


    public function cost(Request $request): JsonResponse
    {
        $user = $request->user();

        $current_company = $user->company;

        $stats = Order::with('wusun:id,name,parent_id')
            ->without('lossPersons')
            ->where(function ($query) use ($request, $current_company) {
                if ($request->input('company_id')) {
                    return $query->whereIn('wusun_company_id', Company::getGroupId($request->input('company_id')));
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
                'wusun_company_id, count(*) as case_total,'
                . 'sum(receivable_count) as receivable_total,' // 预算收入
                . 'sum(total_cost) as cost_total,' // 预算总成本
                . 'sum(other_cost) as other_cost_total,' // 其他成本
                . 'sum(received_amount) as received_total,' // 已收款金额
                . 'sum(invoiced_amount) as invoiced_total' // 已开票金额
            )
            ->groupBy('wusun_company_id')->get()->toArray();

        $firstCompany = ['id' => $current_company->id, 'name' => $current_company->name, 'parent_id' => $current_company->parent_id,
            'case_total' => 0, 'receivable_total' => 0, 'cost_total' => 0, 'other_cost_total' => 0, 'received_total' => 0,
            'invoiced_total' => 0, 'children' => []];

        foreach ($stats as $company) {
            $firstCompany['case_total'] += $company['case_total'];
            $firstCompany['receivable_total'] += $company['receivable_total'];
            $firstCompany['cost_total'] += $company['cost_total'];
            $firstCompany['other_cost_total'] += $company['other_cost_total'];
            $firstCompany['received_total'] += $company['received_total'];
            $firstCompany['invoiced_total'] += $company['invoiced_total'];

            if ($company['wusun']['parent_id'] == $firstCompany['id']) {
                $firstCompany['children'][] = $company;
            }
        }

        foreach ($firstCompany['children'] as &$child) {
            foreach ($stats as $company) {
                $child['children'] = [];

                if ($company['wusun']['parent_id'] == $child['wusun_company_id']) {
                    $child['children'][] = $company;
                    $firstCompany['case_total'] += $company['case_total'];
                    $firstCompany['receivable_total'] += $company['receivable_total'];
                    $firstCompany['cost_total'] += $company['cost_total'];
                    $firstCompany['other_cost_total'] += $company['other_cost_total'];
                    $firstCompany['received_total'] += $company['received_total'];
                    $firstCompany['invoiced_total'] += $company['invoiced_total'];
                    break;
                }
            }
        }

        return success($firstCompany);
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
                . 'sum(other_cost) as other_cost_total,' // 其他成本
                . 'sum(received_amount) as received_total,' // 已收款金额
                . 'sum(invoiced_amount) as invoiced_total' // 已开票金额
            )
            ->groupBy('insurance_company_id')->get();

        return success($result);
    }
}
