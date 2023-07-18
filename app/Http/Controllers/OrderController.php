<?php

namespace App\Http\Controllers;

use App\Http\Requests\OrderRequest;
use App\Models\Company;
use App\Models\CompanyProvider;
use App\Models\Enumerations\CheckStatus;
use App\Models\Enumerations\CompanyType;
use App\Models\Enumerations\MessageType;
use App\Models\Enumerations\OrderStatus;
use App\Models\Enumerations\Status;
use App\Models\Enumerations\WuSunStatus;
use App\Models\Message;
use App\Models\Order;
use App\Models\OrderQuotation;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;

class OrderController extends Controller
{
    protected int $company_id;
    protected User $user;

    public function __construct()
    {
        $this->user = \request()->user();
        $this->company_id = $this->user->company_id;
    }

    /**
     * 客户选择
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function customer(Request $request): JsonResponse
    {
        $current_company = $request->user()->company;

        if ($current_company?->getRawOriginal('type') == CompanyType::BaoXian->value)
            return success([
                ['id' => $current_company->id, 'name' => $current_company->name]
            ]);

        $customers_id = CompanyProvider::where('provider_id', $current_company->id)
            ->where('status', Status::Normal)
            ->pluck('company_id');

        $customers = Company::whereIn('id', $customers_id)->select(['id', 'name'])->get();

        return success($customers);
    }

    /**
     * 工单列表
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function index(Request $request): JsonResponse
    {
        $user = $request->user();
        $current_company = $user->company;

        $company_id = $request->input('company_id');

        $role = str_replace($user->company_id . '_', '', $user->getRoleNames()->toArray()[0]);

        $userList = Order::with('company:id,name')
            ->where(function ($query) use ($current_company, $company_id) {
                if ($company_id)
                    return match ($current_company->getRawOriginal('type')) {
                        CompanyType::BaoXian->value,
                        CompanyType::WuSun->value => $query->where('insurance_company_id', $company_id),
                        CompanyType::WeiXiu->value => $query->where('wusun_company_id', $company_id),
                    };

                $groupId = Company::getGroupId($current_company->id);

                return match ($current_company->getRawOriginal('type')) {
                    CompanyType::BaoXian->value => $query->whereIn('insurance_company_id', $groupId),
                    CompanyType::WuSun->value => $query->whereIn('wusun_company_id', $groupId)
                        ->OrWhereIn('check_wusun_company_id', $groupId),
                    CompanyType::WeiXiu->value => $query->where('repair_company_id', $current_company->id),
                };
            })->when($role, function ($query, $role) use ($user) {

                switch ($role) {
                    case '查勘人员':
                        $query->where(function ($query) use ($user) {
                            $query->where('creator_id', '=', $user->id)
                                ->orWhere('wusun_check_id', '=', $user->id)
                                ->orWhere('wusun_repair_user_id', '=', $user->id);
                        });
                        break;
                    case '施工经理':
                    case '施工人员':
                        $query->where(function ($query) use ($user) {
                            $query->where('creator_id', '=', $user->id)
                                ->orWhere('wusun_check_id', '=', $user->id)
                                ->orWhere('wusun_repair_user_id', '=', $user->id);
                        });
                        break;
                    case '查勘经理':
                    case 'admin':
                    case '公司管理员':
                        break;
                    default:
                        $query->where('id', null);
                }
            })->when($request->input('post_time_start'), function ($query, $post_time_start) {
                $query->where('post_time', '>', $post_time_start);
            })->when($request->input('post_time_end'), function ($query, $post_time_end) {
                $query->where('post_time', '<=', $post_time_end);
            })->when($request->input('insurance_type'), function ($query, $insurance_type) {
                $query->where('insurance_type', $insurance_type);
            })->when(strlen($order_status = $request->input('order_status')), function ($query) use ($order_status) {
                $query->where('order_status', $order_status);
            })->when(strlen($close_status = $request->input('close_status')), function ($query) use ($close_status) {
                $query->where('close_status', $close_status);
            })->when($request->input('name'), function ($query, $name) {
                $query->where(function ($query) use ($name) {
                    $query->where('order_number', 'like', "%$name%")
                        ->orWhere('case_number', 'like', "%$name%")
                        ->orWhere('license_plate', 'like', "%$name%")
                        ->orWhere('vin', 'like', "%$name%");
                });
            })
            ->orderBy('id', 'desc')
            ->paginate(getPerPage());

        return success($userList);
    }

    /**
     * 新增、编辑
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function form(OrderRequest $request): JsonResponse
    {
        $orderParams = $request->only([
            'insurance_company_id',
            'external_number',
            'case_number',
            'insurance_check_name',
            'insurance_check_phone',
            'post_time',
            'insurance_type',
            'license_plate',
            'vin',
            'locations',
            'province',
            'city',
            'area',
            'address',
            'creator_id',
            'creator_name',
            'insurance_people',
            'insurance_phone',
            'driver_name',
            'driver_phone',
            'remark',
            'customer_remark',
            'order_status',
            'close_status',
            'goods_types',
            'goods_name',
            'owner_name',
            'owner_phone',
            'owner_price',
            'images',
            'goods_remark',
            'review_images',
            'review_remark',
            'review_at'
        ]);

        $user = $request->user();
        $company = $user->company;

        $order = Order::findOr($request->input('id'), fn() => new Order([
            'creator_id' => $user->id,
            'creator_name' => $user->name,
            'creator_company_id' => $company->id,
            'order_number' => Order::genOrderNumber()
        ]));

        $is_create = empty($order->id);

        $order->fill(Arr::whereNotNull($orderParams));

        /**
         * 物损公司自建工单直接派发给自己
         */
        if ($is_create && $company->getRawOriginal('type') == CompanyType::WuSun->value) {
            $order->fill([
                'check_wusun_company_id' => $company->id,
                'check_wusun_company_name' => $company->name,
                'dispatch_check_wusun_at' => now()->toDateTimeString(),
                'order_status' => OrderStatus::WaitCheck->value,
                'dispatched' => true
            ]);

            $order->save();

            // Message
            $message = new Message([
                'send_company_id' => $order->insurance_company_id,
                'to_company_id' => $order->check_wusun_company_id,
                'type' => MessageType::NewOrder->value,
                'order_id' => $order->id,
                'order_number' => $order->order_number,
                'case_number' => $order->case_number,
                'goods_types' => $order->goods_types,
                'remark' => $order->remark,
                'status' => 0,
            ]);
            $message->save();
        }

        $order->save();

        return success($order->load('company:id,name'));
    }

    /**
     * 工单详情
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function detail(Request $request): JsonResponse
    {
        $order = Order::with(['company:id,name,type,logo', 'check_wusun:id,name', 'wusun:id,name'])->find($request->input('id'));

        return success($order);
    }

    /**
     * 派遣物损查勘人员 （物损公司派遣本公司）
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function dispatchCheckUser(Request $request): JsonResponse
    {
        $params = $request->only(['wusun_check_id', 'wusun_check_name', 'wusun_check_phone']);

        $params['dispatch_check_at'] = now()->toDateTimeString();

        try {
            throw_if(!$order = Order::find($request->input('order_id')), '工单未找到');

            throw_if($this->company_id != $order->check_wusun_company_id, '非本公司订单');

            throw_if($order->wusun_check_accept_at, '查勘已完成');

            $company = Company::find($this->company_id);

            throw_if($company->getRawOriginal('type') != CompanyType::WuSun->value, '只有物损公司可以派遣查勘');

            $order->fill($params);
            $order->wusun_status = WuSunStatus::AcceptCheck->value;
            $order->order_status = OrderStatus::Checking->value;
            $order->save();

            // Message
            $message = new Message([
                'send_company_id' => $order->insurance_company_id,
                'to_company_id' => $order->check_wusun_company_id,
                'user_id' => $params['wusun_check_id'],
                'type' => MessageType::NewCheckTask->value,
                'order_id' => $order->id,
                'order_number' => $order->order_number,
                'case_number' => $order->case_number,
                'goods_types' => $order->goods_types,
                'remark' => $order->remark,
                'status' => 0,
            ]);
            $message->save();
        } catch (\Throwable $exception) {
            return fail($exception->getMessage());
        }

        return success();
    }

    /**
     * 完成查勘 （物损查看人员）
     * @param Request $request
     * @return JsonResponse
     */
    public function check(Request $request): JsonResponse
    {
        $order = Order::find($request->input('order_id'));

        if (empty($order) or $order->wusun_check_id != $request->user()->id) return fail('工单不存在或不属于当前账号');

        $order->fill($request->only(['images', 'remark']));

        $order->wusun_status = WuSunStatus::FinishedCheck->value;
        $order->wusun_checked_at = now()->toDateTimeString();
        $order->save();

        return success();
    }

    /**
     * 确认维修方案
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function confirmPlan(Request $request): JsonResponse
    {
        $order = Order::find($request->input('order_id'));
        if (empty($order) or $order->wusun_check_id != $request->user()->id) return fail('工单不存在或不属于当前账号');

        $order->fill($request->only(['plan_type', 'owner_name', 'owner_phone', 'owner_price', 'negotiation_content']));
        $order->plan_confirm_at = now()->toDateTimeString();
        $order->wusun_status = WuSunStatus::ConfirmPlan->value;
        $order->plan_confirm_at = now()->toDateTimeString();

        $order->save();

        return success($order);
    }

    /**
     * 获取某个某单的所有报价 （保险公司开标）
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function quotations(Request $request): JsonResponse
    {
        $quotations = OrderQuotation::where('order_id', $request->input('order_id'))
            ->where('check_status', CheckStatus::Accept->value)->get();

        return success($quotations);
    }

}
