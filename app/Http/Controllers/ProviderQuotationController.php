<?php

namespace App\Http\Controllers;

use App\Common\Messages\WinBidNotify;
use App\Models\Company;
use App\Models\ConsumerOrderDailyStats;
use App\Models\Enumerations\CompanyType;
use App\Models\Enumerations\MessageType;
use App\Models\Message;
use App\Models\Order;
use App\Models\OrderDailyStats;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Overtrue\EasySms\EasySms;
use Overtrue\EasySms\Exceptions\InvalidArgumentException;

class ProviderQuotationController extends Controller
{

    /**
     * 服务商报价管理 （保险）
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function index(Request $request): JsonResponse
    {
        $company_id = $request->input('company_id');
        $current_company = $request->user()->company;

        if ($request->user()->hasRole('admin')) return fail('超级管理员无法查看');

        elseif (empty($current_company)) return fail('所属公司不存在');

        $orders = Order::with('company:id,name')
            ->where('bid_type', '=', 1)
            ->when(strlen($status = $request->input('bid_status')), function ($query) use ($status) {
                $query->where('bid_status', $status);
            })
            ->where(function ($query) use ($current_company, $company_id) {
                if ($company_id)
                    return match ($current_company->getRawOriginal('type')) {
                        CompanyType::BaoXian->value,
                        CompanyType::WuSun->value => $query->where('insurance_company_id', $company_id)
                    };

                $groupId = Company::getGroupId($current_company->id);

                return match ($current_company->getRawOriginal('type')) {
                    CompanyType::BaoXian->value => $query->whereIn('insurance_company_id', $groupId),
                    CompanyType::WuSun->value => $query->whereIn('wusun_company_id', $groupId)
                        ->OrWhereIn('check_wusun_company_id', $groupId),
                };
            })
            ->when($request->input('name'), function ($query, $name) {
                $query->where('order_number', 'like', '%' . $name . '%')
                    ->orWhere('case_number', 'like', '%' . $name . '%')
                    ->orWhere('license_plate', 'like', '%' . $name . '%');
            })
            ->when($request->input('post_time_start'), function ($query, $post_time_start) {
                $query->where('post_time', '>', $post_time_start);
            })
            ->when($request->input('post_time_end'), function ($query, $post_time_end) {
                $query->where('post_time', '<', $post_time_end);
            })
            ->when($request->input('provider_id'), function ($query, $provider_id) use ($current_company) {
                match ($current_company->getRawOriginal('type')) {
                    CompanyType::BaoXian->value => $query->where('wusun_company_id', $provider_id)
                };
            })
            ->orderBy('id', 'desc')
            ->paginate(getPerPage());

        return success($orders);
    }

    /**
     * 核价详情
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function detail(Request $request): JsonResponse
    {
        $with = ['company:id,name', 'quotations', 'quotations.company:id,name', 'quotations.items'];

        $order = Order::with($with)->find($request->input('order_id'));

        if (!$order) return fail('订单不存在');

        if ($request->user()->can('ViewQuotation') && $order->quotations->count() > 0) {
            foreach ($order->quotations as $quotation) {
                if ($quotation->win != 1) {
                    $quotation->bid_repair_days = "**";
                    $quotation->bid_total_price = "*****";
                }
            }
        }

        /**
         * ['price' => 1, 'id' => 1];
         */
        $lower = [];

        foreach ($order->quotations as $quotation) {
            foreach ($quotation->items as $item) {
                $lower[$item->name] = isset($lower[$item->name]) ? (
                $item->price < $lower[$item->name]['price'] ? ['price' => $item->price, 'id' => $item->id] : $lower[$item->name]
                ) : ['price' => $item->price, 'id' => $item->id];
            }
        }

        $order->lower = empty($lower) ? [] : array_column($lower, 'id');

        return success($order);
    }

    /**
     * 手动开标
     *
     * @param Request $request
     * @param EasySms $easySms
     * @return JsonResponse
     * @throws InvalidArgumentException
     */
    public function pick(Request $request, EasySms $easySms): JsonResponse
    {
        $order = Order::find($request->input('order_id'));

        if ($order->bid_type != Order::BID_TYPE_JINGJIA) return fail('非竞价工单，不可手动开标');

        if ($order->bid_status == Order::BID_STATUS_FINISHED) return fail('不可重复开标');

        $quotation = $order->quotations()->where('company_id', $request->input('wusun_company_id'))->first();
        $order->bid_status = Order::BID_STATUS_FINISHED;
        $order->bid_end_time = now()->toDateTimeString();
        $order->confim_wusun_at = $order->bid_end_time;
        $order->bid_win_price = $quotation->bid_total_price;
        $order->fill($request->only([
            'wusun_company_id',
            'wusun_company_name'
        ]));

        $order->quotations()->where('company_id', $request->input('wusun_company_id'))->update([
            'win' => 1,
            'bid_end_time' => now()->toDateTimeString()
        ]);

        $order->quotations()->where('company_id', '<>', $request->input('wusun_company_id'))->update([
            'win' => 2,
            'bid_end_time' => now()->toDateTimeString()
        ]);

        $order->save();

        // Message
        $message = new Message([
            'send_company_id' => $order->insurance_company_id,
            'to_company_id' => $request->input('wusun_company_id'),
            'type' => MessageType::OrderDispatch->value,
            'order_id' => $order->id,
            'order_number' => $order->order_number,
            'case_number' => $order->case_number,
            'goods_types' => $order->goods_types,
            'remark' => $order->remark,
            'status' => 0,
        ]);
        $message->save();

        $company = Company::find($request->input('wusun_company_id'));
        $insuranceCompany = Company::find($order->insurance_company_id);

        OrderDailyStats::updateOrCreate([
            'company_id' => $company->id,
            'parent_id' => $company->parent_id,
            'date' => substr($order->post_time, 0, 10),
        ], [
            'order_count' => DB::raw('order_count + 1')
        ]);

        ConsumerOrderDailyStats::updateOrCreate([
            'company_id' => $company->id,
            'date' => substr($order->post_time, 0, 10),
            'insurance_company_id' => $order->insurance_company_id
        ], [
            'order_count' => DB::raw('order_count + 1')
        ]);

        if ($company->parent_id) { // 同时更新上级工单数量
            $parentCompany = Company::find($company->parent_id);

            OrderDailyStats::updateOrCreate([
                'company_id' => $parentCompany->id,
                'parent_id' => $parentCompany->parent_id,
                'date' => substr($order->post_time, 0, 10),
            ], [
                'order_count' => DB::raw('order_count + 1')
            ]);

            if ($parentCompany->parent_id) {
                $_parentCompany = Company::find($parentCompany->parent_id);
                OrderDailyStats::updateOrCreate([
                    'company_id' => $_parentCompany->id,
                    'parent_id' => $_parentCompany->parent_id,
                    'date' => substr($order->post_time, 0, 10),
                ], [
                    'order_count' => DB::raw('order_count + 1')
                ]);
            }
        }

        try {
            $easySms->send(
                $company->contract_phone,
                new WinBidNotify($company->name, $insuranceCompany->name, $order->case_number)
            );
            if ($company->backup_contract_phone)
                $easySms->send(
                    $company->backup_contract_phone,
                    new WinBidNotify($company->name, $insuranceCompany->name, $order->case_number)
                );
        } catch (\Exception $exception) {
        }

        return success();
    }
}
