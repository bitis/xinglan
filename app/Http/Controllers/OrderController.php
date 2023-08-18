<?php

namespace App\Http\Controllers;

use App\Http\Requests\OrderRequest;
use App\Jobs\CheckMessageJob;
use App\Models\ApprovalOption;
use App\Models\ApprovalOrder;
use App\Models\ApprovalOrderProcess;
use App\Models\Approver;
use App\Models\Company;
use App\Models\CompanyProvider;
use App\Models\Enumerations\ApprovalMode;
use App\Models\Enumerations\ApprovalStatus;
use App\Models\Enumerations\ApprovalType;
use App\Models\Enumerations\CheckStatus;
use App\Models\Enumerations\CompanyType;
use App\Models\Enumerations\MessageType;
use App\Models\Enumerations\OrderCloseStatus;
use App\Models\Enumerations\Status;
use App\Models\Message;
use App\Models\Order;
use App\Models\OrderLog;
use App\Models\OrderQuotation;
use App\Services\OrderService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Vtiful\Kernel\Excel;

class OrderController extends Controller
{

    /**
     * 客户选择
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function customer(Request $request): JsonResponse
    {
        $current_company = $request->user()->company;

        if ($request->user()->hasRole('admin')) return success();

        if (empty($current_company)) return fail('所属公司不存在');

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
        if ($request->user()->hasRole('admin')) return success('超级管理员无法查看');

        $orders = OrderService::list($request->user(), $request->collect(), ['company:id,name'])
            ->selectRaw('orders.*')
            ->orderBy('orders.id', 'desc')
            ->paginate(getPerPage());

        return success($orders);
    }


    /**
     * 导出 Excel
     *
     * @param Request $request
     * @return BinaryFileResponse|JsonResponse
     */
    public function export(Request $request): BinaryFileResponse|JsonResponse
    {
        if ($request->user()->hasRole('admin')) return success('超级管理员无法查看');

        $orders = OrderService::list($request->user(), $request->collect(), ['company:id,name'])
            ->selectRaw('orders.*')
            ->orderBy('orders.id', 'desc')
            ->get();

        $excel = new Excel(['path' => sys_get_temp_dir()]);

        $excel->fileName('工单导出_' . date('YmdHi') . '.xlsx', 'sheet1')
            ->header([
                '订单来源', '工单号', '所属公司', '出险日期', '报案号', '车牌号', '客户名称', '保险查勘员', '保险查勘员电话',
                '物损地点', '省', '市', '区', '工单状态', '结案状态', '车险险种', '结案时间', '物损查勘员', '物损查勘员电话',
                '物损项目', '物损任务名称', '谈判经过', '物损备注', '受损方姓名', '受损方电话', '修复单位', '修复单位编码',
                '施工人员', '施工开始时间', '施工结束时间', '施工备注', '施工成本', '已付成本金额', '成本审核人', '成本审核时间',
                '物损方要价合计', '对外报价金额', '核价（定损）金额', '减损金额', '已收款金额', '已收款明细', '其他成本', '预估成本合计',
                '报销金额合计', '报销金额明细', '已付款金额合计（含报销金额）', '已开票金额', '税金合计', '毛利率', '实际毛利额',
                '对账内勤', '险种', '保单号', '车架号', '被保险人', '被保险电话', '驾驶人', '驾驶人电话', '服务评分', '服务评价'
            ])
            ->data($orders)
            ->output();

        return response()->file('', ['Content-Type' => 'application/vnd.ms-excel']);
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
            'review_at',
            'bid_type',
            'bid_end_time',
        ]);

        $user = $request->user();
        $company = $user->company;

        $order = Order::findOr($request->input('id'), fn() => new Order([
            'creator_id' => $user->id,
            'creator_name' => $user->name,
            'creator_company_id' => $company->id,
            'creator_company_type' => $company->getRawOriginal('type'),
            'order_number' => Order::genOrderNumber()
        ]));

        $is_create = empty($order->id);

        $order->fill(Arr::whereNotNull($orderParams));

        if ($order->isDirty('review_images') or $order->isDirty('review_remark')) {
            $order->review_at = now()->toDateTimeString();
        }

        $order->save();

        if ($insurers = $request->input('insurers')) {
            $order->insurers()->delete();
            $order->insurers()->createMany($insurers);
        }

        if ($is_create) {
            OrderLog::create([
                'order_id' => $order->id,
                'type' => OrderLog::TYPE_NEW_ORDER,
                'creator_id' => $user->id,
                'creator_name' => $user->name,
                'creator_company_id' => $company->id,
                'creator_company_name' => $company->name,
                'remark' => $order->remark,
                'content' => '新建工单',
                'platform' => $request->header('platform'),
            ]);

            /**
             * 物损公司自建工单直接派发给自己
             */
            if ($company->getRawOriginal('type') == CompanyType::WuSun->value) {
                $order->fill([
                    'check_wusun_company_id' => $company->id,
                    'check_wusun_company_name' => $company->name,
                    'wusun_company_id' => $company->id,
                    'wusun_company_name' => $company->name,
                    'confim_wusun_at' => now()->toDateTimeString(),
                    'dispatch_check_wusun_at' => now()->toDateTimeString(),
                    'dispatched' => true,
                ]);

                CheckMessageJob::dispatch($order);

                OrderLog::create([
                    'order_id' => $order->id,
                    'type' => OrderLog::TYPE_DISPATCH_CHECK,
                    'creator_id' => $user->id,
                    'creator_name' => $user->name,
                    'creator_company_id' => $company->id,
                    'creator_company_name' => $company->name,
                    'remark' => $order->remark,
                    'content' => '派遣查勘服务商：' . $company->name,
                    'platform' => $request->header('platform'),
                ]);

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
            } elseif ($order->bid_type == Order::BID_TYPE_JINGJIA) {
                $order->fill([
                    'wusun_check_status' => 2,
                ]);
            }

            $order->save();

        } else {
            if ($order->isDirty('check_wusun_company_id')) {
                OrderLog::create([
                    'order_id' => $order->id,
                    'type' => OrderLog::TYPE_DISPATCH_CHECK,
                    'creator_id' => $user->id,
                    'creator_name' => $user->name,
                    'creator_company_id' => $company->id,
                    'creator_company_name' => $company->name,
                    'content' => '派遣查勘服务商修改为：' . $company->name,
                    'platform' => $request->header('platform'),
                ]);
            }
        }

        return success($order->load(['company:id,name', 'insurers']));
    }

    /**
     * 工单详情
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function detail(Request $request): JsonResponse
    {
        $order = Order::with([
            'company:id,name,type,logo',
            'check_wusun:id,name',
            'wusun:id,name',
            'repair_plan',
            'insurers',
        ])->find($request->input('id'));

        $quotation = OrderQuotation::where('company_id', $request->user()->company_id)->where('order_id', $order->id)->first();

        $order->quotation = $quotation;
        $order->quote_status = 0; // 报价状态 0 未报 1 审核中 2 已报

        if ($quotation?->win) {
            if ($quotation->submit) {
                $order->quote_status++;
                if ($quotation->check_status) {
                    $order->quote_status++;
                }
            }
        }

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

        $user = $request->user();

        try {
            throw_if(!$order = Order::find($request->input('order_id')), '工单未找到');

            throw_if($user->company_id != $order->check_wusun_company_id
                and $user->company_id != $order->wusun_company_id, '非本公司订单');

            throw_if($order->wusun_check_accept_at, '查勘已完成');

            $company = Company::find($user->company_id);

            throw_if($company->getRawOriginal('type') != CompanyType::WuSun->value, '只有物损公司可以派遣查勘');

            DB::beginTransaction();

            $order->fill($params);
            $order->wusun_check_status = Order::WUSUN_CHECK_STATUS_CHECKING;
            $order->save();

            // Message
            $message = new Message([
                'send_company_id' => $user->company_id,
                'to_company_id' => $user->company_id,
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
            DB::commit();
        } catch (\Throwable $exception) {
            DB::rollBack();
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

        $order->wusun_check_status = Order::WUSUN_CHECK_STATUS_FINISHED;
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

    /**
     * 结案
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function close(Request $request): JsonResponse
    {
        $order = Order::find($request->input('order_id'));

        if ($order->close_status == OrderCloseStatus::Closed->value) return fail('该工单已经结案');

        if ($order->close_status == OrderCloseStatus::Closed->value) return fail('该工单已经结案');

        $user = $request->user();
        try {
            DB::beginTransaction();
            $order->guarantee_period = $request->input('guarantee_period');
            $order->close_remark = $request->input('close_remark');
            $order->close_status = OrderCloseStatus::Check->value;
            $order->close_at = now()->toDateTimeString();
            $order->save();

            $option = ApprovalOption::findByType($user->company_id, ApprovalType::ApprovalClose->value);

            ApprovalOrder::where('order_id', $order->id)->where('company_id', $order->wusun_company_id)->delete();
            ApprovalOrderProcess::where('order_id', $order->id)->where('company_id', $order->wusun_company_id)->delete();

            $approvalOrder = ApprovalOrder::create([
                'order_id' => $order->id,
                'company_id' => $user->company_id,
                'approval_type' => $option->type,
            ]);

            list($checkers, $reviewers, $receivers) = ApprovalOption::groupByType($option->approver);

            $insert = [];
            foreach ($checkers as $index => $checker) {
                $insert[] = [
                    'user_id' => $checker['id'],
                    'name' => $checker['name'],
                    'creator_id' => $user->id,
                    'creator_name' => $user->name,
                    'order_id' => $order->id,
                    'company_id' => $user->company_id,
                    'step' => Approver::STEP_CHECKER,
                    'approval_status' => ApprovalStatus::Pending->value,
                    'mode' => $option->approve_mode,
                    'approval_type' => $option->type,
                    'hidden' => $index > 0 && $option->approve_mode == ApprovalMode::QUEUE->value,
                ];
            }

            foreach ($receivers as $receiver) {
                $insert[] = [
                    'user_id' => $receiver['id'],
                    'name' => $receiver['name'],
                    'creator_id' => $user->id,
                    'creator_name' => $user->name,
                    'order_id' => $order->id,
                    'company_id' => $user->company_id,
                    'step' => Approver::STEP_RECEIVER,
                    'approval_status' => ApprovalStatus::Pending->value,
                    'mode' => ApprovalMode::QUEUE->value,
                    'approval_type' => $option->type,
                    'hidden' => true,
                ];
            }

            $approvalOrder->process()->delete();
            if ($insert) $approvalOrder->process()->createMany($insert);

            DB::commit();
        } catch (\Exception $exception) {
            DB::rollBack();
            return fail($exception->getMessage());
        }

        return success();
    }

    /**
     * 工单变动日志
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function logs(Request $request): JsonResponse
    {
        $user = $request->user();

        $company = $user->company;

        $types = match ($company->getRawOriginal('type')) {
            CompanyType::WuSun->value => [],
            CompanyType::BaoXian->value => [],
        };

        $logs = OrderLog::where('order_id', $request->input('order_id'))
            ->orderBy('id', 'desc')
            ->get();

        return success($logs);
    }
}
