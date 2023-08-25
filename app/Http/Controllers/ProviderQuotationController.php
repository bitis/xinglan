<?php

namespace App\Http\Controllers;

use App\Common\Messages\WinBidNotify;
use App\Models\ApprovalOption;
use App\Models\ApprovalOrder;
use App\Models\ApprovalOrderProcess;
use App\Models\Approver;
use App\Models\Company;
use App\Models\Enumerations\ApprovalMode;
use App\Models\Enumerations\ApprovalStatus;
use App\Models\Enumerations\ApprovalType;
use App\Models\Enumerations\CompanyType;
use App\Models\Enumerations\MessageType;
use App\Models\Message;
use App\Models\Order;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Overtrue\EasySms\EasySms;

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

        $order = Order::with(['company:id,name', 'quotations', 'quotations.company:id,name'])->find($request->input('order_id'));

        if (!$order) return fail('订单不存在');

        return success($order);
    }

    /**
     * 手动开标
     *
     * @param Request $request
     * @param EasySms $easySms
     * @return JsonResponse
     */
    public function pick(Request $request, EasySms $easySms): JsonResponse
    {
        $order = Order::find($request->input('order_id'));

        if ($order->bid_type != Order::BID_TYPE_JINGJIA) return fail('非竞价工单，不可手动开标');

        if ($order->bid_status == Order::BID_STATUS_FINISHED) return fail('不可重复开标');

        $order->bid_status = Order::BID_STATUS_FINISHED;
        $order->bid_end_time = now()->toDateTimeString();
        $order->confim_wusun_at = $order->bid_end_time;
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

        $easySms->send(
            $company->contract_phone,
            new WinBidNotify($company->name, $insuranceCompany->name, $order->case_number)
        );

        return success();
    }
}
