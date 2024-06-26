<?php

namespace App\Http\Controllers;

use App\Http\Requests\OrderRequest;
use App\Jobs\ApprovalNotifyJob;
use App\Jobs\BidOpeningJob;
use App\Jobs\OrderDispatch;
use App\Jobs\QuotaMessageJob;
use App\Models\ApprovalOption;
use App\Models\ApprovalOrder;
use App\Models\ApprovalOrderProcess;
use App\Models\Approver;
use App\Models\BidOption;
use App\Models\Company;
use App\Models\CompanyProvider;
use App\Models\ConsumerOrderDailyStats;
use App\Models\Enumerations\ApprovalMode;
use App\Models\Enumerations\ApprovalStatus;
use App\Models\Enumerations\ApprovalType;
use App\Models\Enumerations\CompanyType;
use App\Models\Enumerations\InsuranceType;
use App\Models\Enumerations\MessageType;
use App\Models\Enumerations\OrderCloseStatus;
use App\Models\Enumerations\Status;
use App\Models\FinancialOrder;
use App\Models\FinancialPaymentRecord;
use App\Models\Message;
use App\Models\Order;
use App\Models\OrderDailyStats;
use App\Models\OrderLog;
use App\Models\OrderQuotation;
use App\Models\PaymentAccount;
use App\Models\User;
use App\Services\ExportService;
use App\Services\OrderService;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
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

        $customers_id = CompanyProvider::whereIn('provider_id', Company::getGroupId($current_company->id))
            ->where('status', Status::Normal)
            ->pluck('company_id');

        $customers = Company::whereIn('id', $customers_id)->select(['id', 'name'])->get();

        return success($customers);
    }

    /**
     * 工单列表
     *
     * @param Request $request
     * @return JsonResponse|void
     */
    public function index(Request $request)
    {
        $orders = OrderService::list($request->user(), $request->collect(), ['company:id,name'])
            ->selectRaw('orders.*')
            ->orderBy('orders.id', 'desc');

        if (!$request->input('export')) {
            return success($orders->paginate(getPerPage()));
        }

        $result = [];

        $recordToStr = function ($records) {
            $str = '';
            foreach ($records as $record) {
                $str .= $record['payment_time'] . ',收款金额' . $record['amount'] . ';' . PHP_EOL;
            }
            return $str;
        };

        $exportType = $request->input('export_type');

        if ($exportType == 'gdmx') {

            $headers = ['订单来源', '工单号', '所属公司', '出险日期', '报案号', '车牌号', '客户名称', '保险查勘员', '保险查勘员电话',
                '物损地点', '省', '市', '区', '工单状态', '结案状态', '车险险种', '结案时间', '物损查勘员', '物损查勘员电话', '物损项目',
                '物损任务名称', '谈判经过', '物损备注', '受损方姓名', '受损方电话', '修复单位', '修复单位编码', '施工人员', '施工开始时间',
                '施工结束时间', '施工备注', '施工成本', '已付成本金额', '成本审核人', '成本审核时间', '物损方要价合计', '对外报价金额',
                '核价（定损）金额', '减损金额', '已收款金额', '已收款明细', '其他成本', '预估成本合计', '报销金额合计', '报销金额明细',
                '已付款金额合计（含报销金额）', '已开票金额', '税金合计', '毛利率', '实际毛利额', '对账内勤', '险种', '保单号', '车架号',
                '被保险人', '被保险电话', '驾驶人', '驾驶人电话', '服务评分', '服务评价'];

            $fileName = 'gdmx';

            $rows = $orders->with([
                'repair_plan',
                'pure_quotation' => function ($query) {
                    return $query->select(['id', 'order_id', 'total_price']);
                },
                'payment_records:order_id,company_id,payment_time,amount,baoxiao,financial_type'
            ])->get()->toArray();

            foreach ($rows as $item) {
                $result[] = [
                    $item['creator_company_type'] == CompanyType::WuSun->value ? '自建订单' : '保险公司订单',
                    $item['order_number'],
                    $item['wusun_company_name'],
                    date('Y-m-d', strtotime($item['post_time'])), // 出险日期
                    $item['case_number'],
                    $item['license_plate'],
                    $item['insurance_company_name'], // 客户名称
                    $item['insurance_check_name'], // 保险查勘员
                    $item['insurance_check_phone'], // 保险查勘员电话
                    $item['province'] . $item['city'] . $item['area'] . $item['address'], // 物损地点
                    $item['province'],
                    $item['city'],
                    $item['area'],
                    '', // 工单状态
                    ($item['close_status'] == OrderCloseStatus::Closed ? '已结案' : '未结案'), // 结案状态
                    InsuranceType::from($item['insurance_type'])->name(),
                    $item['close_at'], // 结案时间
                    $item['wusun_check_name'], // 物损查勘员
                    $item['wusun_check_phone'], // 物损查勘员电话
                    $item['goods_name'], // 物损项目
                    $item['goods_types'], // 物损任务名称
                    $item['negotiation_content'], // 谈判经过
                    $item['goods_remark'], // 物损备注
                    implode(',', array_column($item['loss_persons'], 'owner_name')), // 受损方姓名
                    implode(',', array_column($item['loss_persons'], 'owner_phone')), // 受损方电话
                    $item['repair_plan'] ? $item['repair_plan']['repair_company_name'] : '', // 修复单位
                    '', // 修复单位编码
                    $item['repair_plan'] ? $item['repair_plan']['repair_user_name'] : '', // 施工人员
                    $item['repair_plan'] ? $item['repair_plan']['repair_start_at'] : '', // 施工开始时间
                    $item['repair_plan'] ? $item['repair_plan']['repair_end_at'] : '', // 施工结束时间
                    $item['repair_plan'] ? $item['repair_plan']['repair_remark'] : '', // 施工备注
                    $item['repair_plan'] ? $item['repair_plan']['repair_cost'] : '', // 施工成本
                    $recordToStr(Arr::where($item['payment_records'], function ($record) {
                        return $record['financial_type'] == 2 && $record['baoxiao'] == 0;
                    })), // 已付成本金额
                    '', // 成本审核人
                    '', // 成本审核时间
                    $item['owner_price'], // 物损方要价合计
                    $item['pure_quotation'] ? $item['pure_quotation']['total_price'] : '', // 对外报价金额
                    $item['confirm_price_status'] == Order::CONFIRM_PRICE_STATUS_FINISHED ? $item['confirmed_price'] : '', // 核价（定损）金额
                    '', // 减损金额
                    $item['received_amount'], // 已收款金额
                    $recordToStr(Arr::where($item['payment_records'], function ($record) {
                        return $record['financial_type'] == 1;
                    })), // 已收款明细
                    $item['other_cost'], // 其他成本
                    $item['total_cost'], // 预估成本合计
                    $item['baoxiao_amount'], // 报销金额合计
                    $recordToStr(Arr::where($item['payment_records'], function ($record) {
                        return $record['financial_type'] == 2 && $record['baoxiao'] == 1;
                    })), // 报销金额明细
                    $item['paid_amount'], // 已付款金额合计（含报销金额）
                    $item['invoiced_amount'], // 已开票金额
                    '', // 税金合计
                    $item['profit_margin_ratio'], // 毛利率
                    '', // 实际毛利额
                    '', // 对账内勤
                    '', // 险种
                    '', // 保单号
                    $item['vin'], // 车架号
                    $item['insurance_people'], // 被保险人
                    $item['insurance_phone'], // 被保险电话
                    $item['driver_name'], // 驾驶人
                    $item['driver_phone'], // 驾驶人电话
                    '',
                    ''
                ];
            }
        } elseif ($exportType == 'gdmxb') {
            $headers = ['工单号', '报案号', '保险公司', '车牌号', '所属公司', '物损类别', '险种类型', '预估损失', '定损金额',
                '减损金额', '受损方姓名', '受损方电话', '物损地点', '省', '市', '区', '工单状态', '保险查勘人', '保险查勘人电话',
                '推修时间', '首次联系物损方时间', '联系时效(分钟数)', '完成查勘时间', '处理方案', '查勘备注', '施工单位', '物损查勘人',
                '物损查勘人电话', '施工开始时间', '修复时间', '服务评分', '服务评价'];

            $fileName = 'gdmxb';

            $rows = $orders->with([
                'repair_plan'
            ])->get()->toArray();

            foreach ($rows as $item) {

                $confirmed_price = Order::CONFIRM_PRICE_STATUS_FINISHED ? $item['confirmed_price'] : '';
                $result[] = [
                    $item['order_number'],
                    $item['case_number'],
                    $item['insurance_company_name'],
                    $item['license_plate'],
                    $item['wusun_company_name'],
                    $item['goods_types'], // 物损类别
                    InsuranceType::from($item['insurance_type'])->name(), // 险种类型
                    $item['owner_price'], // 预估损失
                    $confirmed_price, // 定损金额
                    round($confirmed_price - $item['owner_price'], 2), // 减损金额
                    implode(',', array_column($item['loss_persons'], 'owner_name')), // 受损方姓名
                    implode(',', array_column($item['loss_persons'], 'owner_phone')), // 受损方电话
                    $item['province'] . $item['city'] . $item['area'] . $item['address'], // 物损地点
                    $item['province'],
                    $item['city'],
                    $item['area'],
                    '', // 工单状态
                    $item['insurance_check_name'], // 保险查勘人
                    $item['insurance_check_phone'], // 保险查勘人电话
                    '', // 推修时间
                    '', // 首次联系物损方时间
                    '', // 联系时效(分钟数)
                    $item['wusun_checked_at'], // 完成查勘时间
                    ['', '施工修复', '协调处理'][$item['plan_type']], // 处理方案
                    $item['remark'], // 查勘备注
                    $item['repair_plan'] ? $item['repair_plan']['repair_company_name'] : '', // 修复单位, // 施工单位
                    $item['wusun_check_name'], // 物损查勘人
                    $item['wusun_check_phone'], // 物损查勘人电话
                    $item['repair_plan'] ? $item['repair_plan']['repair_start_at'] : '', // 施工开始时间
                    $item['repair_plan'] ? $item['repair_plan']['repair_end_at'] : '', // 施工结束时间
                    '',
                    ''
                ];
            }
        } elseif ($exportType == 'bx') { // 保险导出
            $headers = ['序号', '公司名称', '竞价案件', '受损方要价', '最终中标金额', '是否中标', '是否为派发案件', '减损金额', '减损率', '竞价状态'];
            $fileName = 'baoxian';
            $rows = $orders->get()->toArray();

            foreach ($rows as $index => $row) {
                $discount_price = round($row['owner_price'] - $row['bid_win_price'], 2);
                if (empty($row['owner_price']) or empty($row['owner_price'] / 1) or $row['bid_status'] != 1) $discount_ratio = '0.00%';
                else $discount_ratio = round($discount_price / $row['owner_price'] * 100, 2) . '%';

                $result[] = [
                    $index,
                    $row['check_wusun_company_name'],
                    $row['bid_type'] == Order::BID_TYPE_JINGJIA ? '是' : '否', // 竞价案件
                    $row['owner_price'], // 受损方要价
                    $row['bid_win_price'], // 最终中标金额
                    empty($row['bid_win_price']) ? '否' : '是', // 是否中标
                    $row['bid_type'] == Order::BID_TYPE_FENPAI ? '是' : '否', // 是否为派发案件
                    $discount_price, // 减损金额
                    $discount_ratio, // 减损率
                    $row['bid_status']
                ];
            }
        }

        (new ExportService)->excel($headers, $result, $fileName);
    }


    /**
     * 新增、编辑
     *
     * @param OrderRequest $request `
     * @return JsonResponse
     * @throws \Exception
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
            'with_quotation'
        ]);
        $lossPersons = $request->input('lossPersons', []);

        if (!$request->input('id')) {
            if ($request->input('bid_type') == 1 && $request->input('insurance_type') != InsuranceType::CarPart->value && !$request->input('bid_end_time')) {
                return fail('请选择报价截止时间');
            }

            if ($request->input('bid_end_time') && now()->gt($request->input('bid_end_time'))) {
                return fail('报价截止时间不能小于当前时间');
            }
        } else {
            unset($orderParams['bid_type']);
            unset($orderParams['bid_end_time']);
        }

        $user = $request->user();
        $company = $user->company;

        try {
            DB::beginTransaction();
            $order = Order::findOr($request->input('id'), fn() => new Order([
                'creator_id' => $user->id,
                'creator_name' => $user->name,
                'creator_company_id' => $company->id,
                'creator_company_type' => $company->getRawOriginal('type'),
                'order_number' => Order::genOrderNumber()
            ]));

            if ($order->close_status == OrderCloseStatus::Closed) return fail('已结案工单不可进行操作');

            $is_create = empty($order->id);

            $order->fill(Arr::whereNotNull($orderParams));
            $order->insurance_company_name = Company::find($order->insurance_company_id)?->name;

            if ($order->isDirty('review_images') or $order->isDirty('review_remark')) {
                $order->review_at = now()->toDateTimeString();

                // 复勘审批
                $option = ApprovalOption::findByType($order->insurance_company_id, ApprovalType::ApprovalRepaired->value)
                    ?: ApprovalOption::findByType($user->company_id, ApprovalType::ApprovalRepaired->value);

                $checker_text = '';

                if ($option) {
                    $order->review_at = null;
                    $approvalOrder = ApprovalOrder::where('order_id', $order->id)->where('approval_type', $option->type)->orderBy('id', 'desc')->first();
                    if ($approvalOrder) {
                        $approvalOrder->process()->update(['history' => true]);
                        $approvalOrder->update(['history' => true]);
                    }

                    $approvalOrder = ApprovalOrder::create([
                        'order_id' => $order->id,
                        'company_id' => $option->company_id,
                        'approval_type' => $option->type,
                        'creator_id' => $user->id,
                        'creator_name' => $user->name,
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
                            'company_id' => $option->company_id,
                            'step' => Approver::STEP_CHECKER,
                            'approval_status' => ApprovalStatus::Pending->value,
                            'mode' => $option->approve_mode,
                            'approval_type' => $option->type,
                            'hidden' => $index > 0 && $option->approve_mode == ApprovalMode::QUEUE->value,
                        ];
                        $checker_text .= $checker['name'] . ', ';
                    }

                    $checker_text = '审核人：（' . trim($checker_text, ',') . '）' . ['', '或签', '依次审批'][$option->approve_mode];

                    foreach ($receivers as $receiver) {
                        $insert[] = [
                            'user_id' => $receiver['id'],
                            'name' => $receiver['name'],
                            'creator_id' => $user->id,
                            'creator_name' => $user->name,
                            'order_id' => $order->id,
                            'company_id' => $option->company_id,
                            'step' => Approver::STEP_RECEIVER,
                            'approval_status' => ApprovalStatus::Pending->value,
                            'mode' => ApprovalMode::QUEUE->value,
                            'approval_type' => $option->type,
                            'hidden' => true,
                        ];
                    }

                    if ($insert) $approvalOrder->process()->createMany($insert);

                    foreach ($approvalOrder->process as $process) {
                        if (!$process->hidden) ApprovalNotifyJob::dispatch($process['user_id'], [
                            'type' => 'approval',
                            'order_id' => $order->id,
                            'process_id' => $process->id,
                            'creator_name' => $process->creator_name,
                            'approval_type' => $approvalOrder->approval_type,
                        ]);
                    }
                }

                OrderLog::create([
                    'order_id' => $order->id,
                    'type' => OrderLog::TYPE_QUOTATION,
                    'creator_id' => $user->id,
                    'creator_name' => $user->name,
                    'creator_company_id' => $user->company_id,
                    'creator_company_name' => $company->name,
                    'content' => $user->name . '提交复勘资料' . '；备注：' . $order->review_remark . "审批人：" . $checker_text,
                    'platform' => \request()->header('platform'),
                ]);
            }

            if (!empty($lossPersons) && $order->insurance_type != InsuranceType::CarPart->value) {
                $order->goods_types = implode(',', array_column($lossPersons, 'goods_types'));
            }

            $order->save();

            if ($is_create) {
                OrderLog::create([
                    'order_id' => $order->id,
                    'type' => OrderLog::TYPE_NEW_ORDER,
                    'creator_id' => $user->id,
                    'creator_name' => $user->name,
                    'creator_phone' => $user->mobile,
                    'creator_company_id' => $company->id,
                    'creator_company_name' => $company->name,
                    'remark' => $order->remark . "",
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
                        'bid_type' => Order::BID_TYPE_FENPAI,
                        'bid_status' => Order::BID_STATUS_FINISHED,
                        'bid_end_time' => now()->toDateTimeString(),
                    ]);

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

                    OrderDailyStats::updateOrCreate([
                        'company_id' => $company->id,
                        'parent_id' => $company->parent_id,
                        'date' => now()->toDateString(),
                    ], [
                        'order_count' => DB::raw('order_count + 1')
                    ]);

                    ConsumerOrderDailyStats::updateOrCreate([
                        'company_id' => $company->id,
                        'date' => now()->toDateString(),
                        'insurance_company_id' => $order->insurance_company_id
                    ], [
                        'order_count' => DB::raw('order_count + 1')
                    ]);

                    if ($company->parent_id) { // 同时更新上级工单数量
                        $parentCompany = Company::find($company->parent_id);

                        OrderDailyStats::updateOrCreate([
                            'company_id' => $parentCompany->id,
                            'parent_id' => $parentCompany->parent_id,
                            'date' => now()->toDateString(),
                        ], [
                            'order_count' => DB::raw('order_count + 1')
                        ]);

                        if ($parentCompany->parent_id) {
                            $_parentCompany = Company::find($parentCompany->parent_id);
                            OrderDailyStats::updateOrCreate([
                                'company_id' => $_parentCompany->id,
                                'parent_id' => $_parentCompany->parent_id,
                                'date' => now()->toDateString(),
                            ], [
                                'order_count' => DB::raw('order_count + 1')
                            ]);
                        }
                    }
                } else {
                    $order->insurance_check_name = $user->name;
                    $order->insurance_check_phone = $user->mobile;

                    $bidOption = BidOption::findByCompany($order->insurance_company_id);

                    if ($order->insurance_type == InsuranceType::CarPart->value) {
                        // 车件全部竞价
                        $order->dispatched = true;
                        $order->bid_type = Order::BID_TYPE_JINGJIA;
                        if (empty($order->bid_end_time)) {
                            $order->bid_end_time = BidOption::getBidEndTime($order, $bidOption);
                        }

                    } else {
                        // 车险和非车险根据配置是否竞价
                        if ($bidOption && $order->owner_price > $bidOption->bid_first_price && $order->bid_type != Order::BID_TYPE_JINGJIA) {

                            $order->bid_type = Order::BID_TYPE_JINGJIA;
                            $order->bid_status = Order::BID_STATUS_PROGRESSING;
                            $order->bid_end_time = BidOption::getBidEndTime($order, $bidOption);
                            $order->dispatched = true;

                            OrderLog::create([
                                'order_id' => $order->id,
                                'type' => OrderLog::TYPE_DISPATCH_CHECK,
                                'creator_id' => $user->id,
                                'creator_name' => $user->name,
                                'creator_company_id' => $company->id,
                                'creator_company_name' => $company->name,
                                'remark' => $order->remark,
                                'content' => '根据系统配置竞价规则，当前工单改为竞价，竞价截止时间：' . $order->bid_end_time,
                                'platform' => 'system',
                            ]);
                        }
                    }

                    if ($order->bid_type == Order::BID_TYPE_JINGJIA) {
                        $order->fill([
                            'wusun_check_status' => 2,
                        ]);
                    }
                }

                if ($company->getRawOriginal('type') == CompanyType::BaoXian->value && $request->input('wusun_company_id')) {
                    $wusun = Company::find($request->input('wusun_company_id'));

                    if (empty($wusun)) throw new \Exception('指定的物损公司不存在');

                    $order->fill([
                        'check_wusun_company_id' => $wusun->id,
                        'check_wusun_company_name' => $wusun->name,
                        'wusun_company_id' => $wusun->id,
                        'wusun_company_name' => $wusun->name,
                        'confim_wusun_at' => now()->toDateTimeString(),
                        'dispatch_check_wusun_at' => now()->toDateTimeString(),
                        'dispatched' => true,
                        'bid_type' => Order::BID_TYPE_FENPAI,
                        'bid_status' => Order::BID_STATUS_FINISHED,
                        'bid_end_time' => now()->toDateTimeString(),
                    ]);

                    OrderLog::create([
                        'order_id' => $order->id,
                        'type' => OrderLog::TYPE_DISPATCH_CHECK,
                        'creator_id' => $user->id,
                        'creator_name' => $user->name,
                        'creator_company_id' => $company->id,
                        'creator_company_name' => $company->name,
                        'remark' => $order->remark,
                        'content' => '派遣查勘服务商：' . $wusun->name,
                        'platform' => $request->header('platform'),
                    ]);

                    // Message
                    Message::create([
                        'send_company_id' => $order->insurance_company_id,
                        'to_company_id' => $wusun->id,
                        'type' => MessageType::NewOrder->value,
                        'order_id' => $order->id,
                        'order_number' => $order->order_number,
                        'case_number' => $order->case_number,
                        'goods_types' => $order->goods_types,
                        'remark' => $order->remark,
                        'status' => 0,
                    ]);
                }

                $order->save();

                // 创建工单后
                if ($order->bid_type != Order::BID_TYPE_JINGJIA) OrderDispatch::dispatch($order);
                if ($order->bid_type == Order::BID_TYPE_JINGJIA) {
                    BidOpeningJob::dispatch($order->id)->delay(Carbon::createFromTimeString($order->bid_end_time));
                    QuotaMessageJob::dispatch($order);
                }

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

            if ($insurers = $request->input('insurers')) {
                $order->insurers()->delete();
                $order->insurers()->createMany($insurers);
            }

            $order->lossPersons()->delete();
            if (!empty($lossPersons) && $order->insurance_type != InsuranceType::CarPart->value) {
                $order->lossPersons()->createMany($lossPersons);
            }
            DB::commit();
        } catch (\Exception $exception) {
            DB::rollBack();
            throw $exception;
            return fail($exception->getMessage());
        }

        return success($order->load(['company:id,name', 'insurers', 'lossPersons']));
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

        $quotation = OrderQuotation::whereIn('company_id', Company::getGroupId($request->user()->company_id))->where('order_id', $order->id)->first();

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
            throw_if($order->close_status == OrderCloseStatus::Closed, '已结案工单不可进行操作');
            throw_if($user->company_id != $order->check_wusun_company_id
                and $user->company_id != $order->wusun_company_id, '非本公司订单');

            $company = Company::find($user->company_id);

            throw_if($company->getRawOriginal('type') != CompanyType::WuSun->value, '只有物损公司可以派遣查勘');

            DB::beginTransaction();

            $order->fill($params);
            if ($order->wusun_check_status == Order::WUSUN_CHECK_STATUS_WAITING) {
                $order->wusun_check_status = Order::WUSUN_CHECK_STATUS_CHECKING;
            }
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

            OrderLog::create([
                'order_id' => $order->id,
                'type' => OrderLog::TYPE_DISPATCH_CHECK_USER,
                'creator_id' => $user->id,
                'creator_name' => $user->name,
                'creator_company_id' => $company->id,
                'creator_company_name' => $company->name,
                'remark' => $order->remark,
                'content' => '派遣查勘人员：' . User::find($params['wusun_check_id'])?->name,
                'platform' => $request->header('platform'),
            ]);
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
        $user = $request->user();
        $company = $user->company;
        $order = Order::find($request->input('order_id'));

        if (empty($order) or $order->wusun_check_id != $request->user()->id) return fail('工单不存在或不属于当前账号');
        if ($order->close_status == OrderCloseStatus::Closed) return fail('已结案工单不可进行操作');

        $order->fill($request->only(['images', 'remark']));

        $order->wusun_check_status = Order::WUSUN_CHECK_STATUS_FINISHED;
        $order->wusun_checked_at = now()->toDateTimeString();
        $order->save();

        OrderLog::create([
            'order_id' => $order->id,
            'type' => OrderLog::TYPE_DISPATCHED,
            'creator_id' => $user->id,
            'creator_name' => $user->name,
            'creator_company_id' => $company->id,
            'creator_company_name' => $company->name,
            'remark' => $order->remark,
            'content' => $user->name . '完成查勘',
            'platform' => $request->header('platform'),
        ]);

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
        $user = $request->user();
        $company = $user->company;

        try {
            DB::beginTransaction();
            $order = Order::find($request->input('order_id'));
            if (empty($order) or $order->wusun_check_id != $request->user()->id) return fail('工单不存在或不属于当前账号');
            if ($order->close_status == OrderCloseStatus::Closed) return fail('已结案工单不可进行操作');

            if ($order->plan_type != $request->input('plan_type')) {

                $update = true;

                if (empty($order->plan_type)) $update = false;

                $stats_update = $request->input('plan_type') == Order::PLAN_TYPE_REPAIR
                    ? ['order_repair_count' => DB::raw('order_repair_count + 1')]
                    : ['order_mediate_count' => DB::raw('order_mediate_count + 1')];

                if ($update) {
                    if ($order->plan_type == Order::PLAN_TYPE_REPAIR) {
                        $stats_update['order_repair_count'] = DB::raw('order_repair_count - 1');
                    } else {
                        $stats_update['order_mediate_count'] = DB::raw('order_mediate_count - 1');
                    }
                }

                OrderDailyStats::updateOrCreate([
                    'company_id' => $company->id,
                    'parent_id' => $company->parent_id,
                    'date' => substr($order->post_time, 0, 10),
                ], $stats_update);

                ConsumerOrderDailyStats::updateOrCreate([
                    'company_id' => $company->id,
                    'date' => substr($order->post_time, 0, 10),
                    'insurance_company_id' => $order->insurance_company_id
                ], $stats_update);
                DB::rollBack();

                if ($company->parent_id) { // 同时更新上级工单数量
                    $parentCompany = Company::find($company->parent_id);

                    OrderDailyStats::updateOrCreate([
                        'company_id' => $parentCompany->id,
                        'parent_id' => $parentCompany->parent_id,
                        'date' => substr($order->post_time, 0, 10),
                    ], $stats_update);

                    if ($parentCompany->parent_id) {
                        $_parentCompany = Company::find($parentCompany->parent_id);
                        OrderDailyStats::updateOrCreate([
                            'company_id' => $_parentCompany->id,
                            'parent_id' => $_parentCompany->parent_id,
                            'date' => substr($order->post_time, 0, 10),
                        ], $stats_update);
                    }
                }
            }

            $order->fill($request->only(['plan_type', 'owner_name', 'owner_phone', 'owner_price', 'negotiation_content']));
            $order->plan_confirm_at = now()->toDateTimeString();

            $order->save();

            OrderLog::create([
                'order_id' => $order->id,
                'type' => OrderLog::TYPE_DISPATCHED,
                'creator_id' => $user->id,
                'creator_name' => $user->name,
                'creator_company_id' => $company->id,
                'creator_company_name' => $company->name,
                'remark' => $order->remark,
                'content' => $user->name . '确认维修方案',
                'platform' => $request->header('platform'),
            ]);
            DB::commit();
            return success($order);
        } catch (\Exception $exception) {
            DB::rollBack();
            return fail($exception->getMessage());
        }

    }

    /**
     * 成本核算
     *
     * @param Request $request
     * @return JsonResponse
     * @throws \Exception
     */
    public function confirmCost(Request $request): JsonResponse
    {
        $user = $request->user();

        $order = Order::where('wusun_company_id', $user->company_id)->find($request->input('id'));

        if (!$order) return fail('工单未找到');
        if ($order->close_status == OrderCloseStatus::Closed) return fail('已结案工单不可进行操作');

        $quotation = OrderQuotation::where('order_id', $request->input('id'))
            ->where('company_id', $user->company_id)
            ->first();

//        if (!$quotation or $quotation->check_status != CheckStatus::Accept->value) return fail('必须先通过对外报价');

        if (in_array($order->cost_check_status, [1, 2])) return fail('当前状态不允许修改');

        try {
            DB::beginTransaction();

            $order->fill($request->only([
                'repair_cost',
                'other_cost',
                'labor_costs',
                'material_cost',
                'total_cost',
                'cost_remark',
            ]));

            if ($quotation) {
                $quotation->fill($request->only([
                    'repair_cost',
                    'other_cost',
                    'labor_costs',
                    'material_cost',
                    'total_cost',
                ]));
            }

            $order->cost_check_status = Order::COST_CHECK_STATUS_APPROVAL;
            $order->cost_submit_at = now()->toDateTimeString();
            $order->cost_creator_id = $user->id;
            $order->cost_creator_name = $user->name;
            $order->cost_checked_at = null;

            $option = ApprovalOption::findByType($user->company_id, ApprovalType::ApprovalRepairCost->value);

            $checker_text = '';

            if (!$option) {
                $order->cost_check_status = Order::COST_CHECK_STATUS_PASS;
                $order->cost_checked_at = now()->toDateTimeString();
            } else {
                $approvalOrder = ApprovalOrder::where('order_id', $order->id)->where('approval_type', $option->type)->orderBy('id', 'desc')->first();
                if ($approvalOrder) {
                    $approvalOrder->process()->update(['history' => true]);
                    $approvalOrder->update(['history' => true]);;
                }

                $approvalOrder = ApprovalOrder::create([
                    'order_id' => $order->id,
                    'company_id' => $user->company_id,
                    'approval_type' => $option->type,
                    'creator_id' => $user->id,
                    'creator_name' => $user->name,
                ]);

                list($checkers, $reviewers, $receivers) = ApprovalOption::groupByType($option->approver);

                $checker_text = $reviewer_text = '';

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
                    $checker_text .= $checker['name'] . ', ';
                }

                $checker_text = '审核人：（' . trim($checker_text, ',') . '）' . ['', '或签', '依次审批'][$option->approve_mode];

                if ($quotation && $quotation->bid_total_price > 0) {
                    $profit_margin_ratio = ($quotation->bid_total_price - $order->total_cost) / $quotation->bid_total_price;
                    $order->profit_margin_ratio = $profit_margin_ratio;
                    Log::info('毛利率:' . $quotation->order_id, ['profit_margin_ratio' => $profit_margin_ratio, 'review_conditions' => $option->review_conditions]);
                    if ($profit_margin_ratio < $option->review_conditions) {
                        foreach ($reviewers as $reviewer) {
                            $insert[] = [
                                'user_id' => $reviewer['id'],
                                'name' => $reviewer['name'],
                                'creator_id' => $user->id,
                                'creator_name' => $user->name,
                                'order_id' => $order->id,
                                'company_id' => $user->company_id,
                                'step' => Approver::STEP_REVIEWER,
                                'approval_status' => ApprovalStatus::Pending->value,
                                'mode' => $option->review_mode,
                                'approval_type' => $option->type,
                                'hidden' => true,
                            ];
                            $reviewer_text .= $reviewer['name'] . ', ';
                        }
                        $checker_text .= ('复审人：(' . trim($reviewer_text, ',') . '）' . ['', '或签', '依次审批'][$option->review_mode]);
                    }
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

                if ($insert) $approvalOrder->process()->createMany($insert);

                foreach ($approvalOrder->process as $process) {
                    if (!$process->hidden) ApprovalNotifyJob::dispatch($process['user_id'], [
                        'type' => 'approval',
                        'order_id' => $order->id,
                        'process_id' => $process->id,
                        'creator_name' => $process->creator_name,
                        'approval_type' => $approvalOrder->approval_type,
                    ]);
                }
            }

            OrderLog::create([
                'order_id' => $order->id,
                'type' => OrderLog::TYPE_SUBMIT_QUOTATION,
                'creator_id' => $user->id,
                'creator_name' => $user->name,
                'creator_company_id' => $user->company_id,
                'creator_company_name' => $order->wusun_company_name,
                'content' => $user->name . '提交施工成本修复审核，施工成本：' . $order->repair_cost . '；其他成本：'
                    . $order->other_cost . '；总成本：' . $order->total_cost . '；备注：' . $checker_text,
                'platform' => \request()->header('platform'),
            ]);
            $order->save();
            DB::commit();
        } catch (\Exception $exception) {
            DB::rollBack();
            return fail($exception->getMessage());
        }

        return success();
    }

    /**
     * 获取某个某单的所有报价 （保险公司开标）
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function quotations(Request $request): JsonResponse
    {
        $quotations = OrderQuotation::where('order_id', $request->input('order_id'))->get();

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
        $user = $request->user();
        $company = $user->company;
        $order = Order::find($request->input('order_id'));

        if ($order->close_status == OrderCloseStatus::Closed->value) return fail('该工单已经结案');

        if ($order->close_status == OrderCloseStatus::Check->value) return fail('该工单已提交结案，审批中');

        try {
            DB::beginTransaction();
            $order->guarantee_period = $request->input('guarantee_period');
            $order->close_remark = $request->input('close_remark');
            $order->close_status = OrderCloseStatus::Check->value;
            $order->close_at = now()->toDateTimeString();
            $order->save();

            $option = ApprovalOption::findByType($user->company_id, ApprovalType::ApprovalClose->value);

            if (!$option) {
                $order->close_status = OrderCloseStatus::Closed->value;
                $order->close_at = now()->toDateTimeString();
                $order->save();
            } else {

                $approvalOrder = ApprovalOrder::where('order_id', $order->id)->where('approval_type', $option->type)->orderBy('id', 'desc')->first();
                if ($approvalOrder) {
                    $approvalOrder->process()->update(['history' => true]);
                    $approvalOrder->update(['history' => true]);
                }

                $approvalOrder = ApprovalOrder::create([
                    'order_id' => $order->id,
                    'company_id' => $user->company_id,
                    'approval_type' => $option->type,
                    'creator_id' => $user->id,
                    'creator_name' => $user->name,
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

                if ($insert) $approvalOrder->process()->createMany($insert);

                foreach ($approvalOrder->process as $process) {
                    if (!$process->hidden) ApprovalNotifyJob::dispatch($process['user_id'], [
                        'type' => 'approval',
                        'order_id' => $order->id,
                        'process_id' => $process->id,
                        'creator_name' => $process->creator_name,
                        'approval_type' => $approvalOrder->approval_type,
                    ]);
                }
            }

            OrderLog::create([
                'order_id' => $order->id,
                'type' => OrderLog::TYPE_DISPATCHED,
                'creator_id' => $user->id,
                'creator_name' => $user->name,
                'creator_company_id' => $company->id,
                'creator_company_name' => $company->name,
                'remark' => $order->remark,
                'content' => $user->name . '提交结案申请',
                'platform' => $request->header('platform'),
            ]);

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
            CompanyType::WuSun->value,
            CompanyType::BaoXian->value => [OrderLog::TYPE_NEW_ORDER, OrderLog::TYPE_BID_OPEN],
            CompanyType::WeiXiu->value => [OrderLog::TYPE_SUBMIT_QUOTATION],
        };

        $logs = OrderLog::where('order_id', $request->input('order_id'))
            ->where(function ($query) use ($user) {
                $query->whereIn('creator_company_id', Company::getGroupId($user->company_id))
                    ->orWhereIn('type', [OrderLog::TYPE_NEW_ORDER, OrderLog::TYPE_BID_OPEN, OrderLog::TYPE_REBID]);
            })
            ->orderBy('id', 'desc')
            ->get();

        return success($logs);
    }

    /**
     * 设置为维修方报价
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function setQuota(Request $request): JsonResponse
    {
        $user = $request->user();
        $company = $user->company;

        $order = Order::find($request->input('order_id'));
        if (empty($order) or $order->wusun_company_id != $company->id) return fail('工单不存在');
        if ($order->close_status == OrderCloseStatus::Closed) return fail('已结案工单不可进行操作');

        $order->repair_bid_type = intval($request->input('repair_bid_type'));
        $order->repair_bid_publish_at = now()->toDateTimeString();
        $order->save();

        if ($order->isDirty('repair_bid_type')) {

            $operateText = $order->repair_bid_type ? '关闭' : '打开';

            OrderLog::create([
                'order_id' => $order->id,
                'type' => OrderLog::TYPE_REPAIR_BID,
                'creator_id' => $user->id,
                'creator_name' => $user->name,
                'creator_company_id' => $company->id,
                'creator_company_name' => $company->name,
                'remark' => $order->remark,
                'content' => $user->name . $operateText . '维修方报价',
                'platform' => $request->header('platform'),
            ]);
        }

        return success();
    }

    /**
     * 重新开标
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function reQuota(Request $request): JsonResponse
    {
        $order = Order::where('insurance_company_id', $request->user()->company_id)
            ->where('id', $request->input('order_id'))
            ->first();

        $user = $request->user();
        $company = $user->company;

        if (!$order or $order->insurance_company_id != $user->company_id) return fail('工单不存在或无权操作');

        if ($order->close_status == OrderCloseStatus::Closed) return fail('已关闭工单不可重新竞价');

        if ($order->bid_type != Order::BID_TYPE_JINGJIA) return fail('非竞价工单不可以重新竞价');


        try {
            DB::beginTransaction();

            $quotations = OrderQuotation::where('order_id', $request->input('order_id'))->get();

            foreach ($quotations as $quotation) {
                $quotation->items()->delete();
                $quotation->delete();
            }

            if ($order->wusun_company_id) {
                $company = Company::find($order->wusun_company_id);

                if ($order->plan_type == Order::PLAN_TYPE_REPAIR)
                    $stats_update = ['order_repair_count' => DB::raw('order_repair_count - 1')];
                elseif ($order->plan_type == Order::PLAN_TYPE_MEDIATE)
                    $stats_update = ['order_mediate_count' => DB::raw('order_mediate_count - 1')];

                OrderDailyStats::updateOrCreate([
                    'company_id' => $company->id,
                    'parent_id' => $company->parent_id,
                    'date' => substr($order->post_time, 0, 10),
                ], array_merge($stats_update, [
                    'order_count' => DB::raw('order_count - 1')
                ]));

                ConsumerOrderDailyStats::updateOrCreate([
                    'company_id' => $company->id,
                    'date' => substr($order->post_time, 0, 10),
                    'insurance_company_id' => $order->insurance_company_id
                ], array_merge($stats_update, [
                    'order_count' => DB::raw('order_count - 1')
                ]));

                if ($company->parent_id) { // 同时更新上级工单数量
                    $parentCompany = Company::find($company->parent_id);

                    OrderDailyStats::updateOrCreate([
                        'company_id' => $parentCompany->id,
                        'parent_id' => $parentCompany->parent_id,
                        'date' => substr($order->post_time, 0, 10),
                    ], array_merge($stats_update, [
                        'order_count' => DB::raw('order_count - 1')
                    ]));

                    if ($parentCompany->parent_id) {
                        $_parentCompany = Company::find($parentCompany->parent_id);
                        OrderDailyStats::updateOrCreate([
                            'company_id' => $_parentCompany->id,
                            'parent_id' => $_parentCompany->parent_id,
                            'date' => substr($order->post_time, 0, 10),
                        ], array_merge($stats_update, [
                            'order_count' => DB::raw('order_count - 1')
                        ]));
                    }
                }
            }

            $order->check_wusun_company_id = null;
            $order->check_wusun_company_name = null;
            $order->dispatch_check_wusun_at = null;
            $order->accept_check_wusun_at = null;
            $order->dispatched = 0;
            $order->wusun_company_id = null;
            $order->wusun_company_name = null;
            $order->confim_wusun_at = null;
            $order->wusun_check_id = null;
            $order->wusun_check_name = null;
            $order->wusun_check_phone = null;
            $order->wusun_check_accept_at = null;
            $order->dispatch_check_at = null;

            $order->bid_win_price = 0;
            $order->bid_status = Order::BID_STATUS_PROGRESSING;
            $order->bid_end_time = BidOption::getBidEndTime($order, BidOption::findByCompany($order->wusun_company_id));
            $order->bid_remark = $request->input('bid_remark');
            $order->save();

            OrderLog::create([
                'order_id' => $order->id,
                'type' => OrderLog::TYPE_REBID,
                'creator_id' => $user->id,
                'creator_name' => $user->name,
                'creator_company_id' => $company->id,
                'creator_company_name' => $company->name,
                'remark' => $order->remark,
                'content' => $user->name . '作废原有开标记录，重新发起竞价',
                'platform' => $request->header('platform'),
            ]);

            ApprovalOrderProcess::where('order_id', $order->id)->where('approval_status', ApprovalStatus::Pending)->update([
                'approval_status' => ApprovalStatus::Rejected,
            ]);
            ApprovalOrder::where('order_id', $order->id)->whereNull('completed_at')->update([
                'completed_at' => now(),
            ]);
            BidOpeningJob::dispatch($order->id)->delay(Carbon::createFromTimeString($order->bid_end_time));
            QuotaMessageJob::dispatch($order);
            DB::commit();
        } catch (\Exception $exception) {
            DB::rollBack();
            return fail($exception->getMessage());
        }

        return success();
    }

    /**
     * 申请付款
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function applyPayment(Request $request): JsonResponse
    {
        $user = $request->user();

        $order = Order::find($request->input('order_id'));

//        if ($order->cost_check_status != Order::COST_CHECK_STATUS_PASS) return fail('没有成本核算时不能申请支付');

        $payees = $request->input('payees');

        $option = ApprovalOption::findByType($user->company_id, ApprovalType::ApprovalPayment->value);

        $add_amount = 0;

        foreach ($payees as $payee) {
            $add_amount += $payee['total_amount'];

            if (!$option) {
                $order->payable_count += $payee['total_amount'];
                $payee['check_status'] = 1;
            }

            FinancialOrder::createByOrder($order, $payee);
            $account = array_merge(Arr::only($payee, ['payment_name', 'payment_bank', 'payment_account']), [
                'company_id' => $user->company_id, 'user_id' => $user->id
            ]);

            if (PaymentAccount::where($account)->doesntExist()) {
                PaymentAccount::create($account);
            }
        }
        $checker_text = $reviewer_text = '';

        if ($option) {
            $approvalOrder = ApprovalOrder::where('order_id', $order->id)->where('approval_type', $option->type)->orderBy('id', 'desc')->first();
            if ($approvalOrder) {
                $approvalOrder->process()->update(['history' => true]);
                $approvalOrder->update(['history' => true]);
            }

            $approvalOrder = ApprovalOrder::create([
                'order_id' => $order->id,
                'company_id' => $user->company_id,
                'approval_type' => $option->type,
                'creator_id' => $user->id,
                'creator_name' => $user->name,
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

            if ($order->profit_margin_ratio < $option->review_conditions) {
                foreach ($reviewers as $reviewer) {
                    $insert[] = [
                        'user_id' => $reviewer['id'],
                        'name' => $reviewer['name'],
                        'creator_id' => $user->id,
                        'creator_name' => $user->name,
                        'order_id' => $order->id,
                        'company_id' => $user->company_id,
                        'step' => Approver::STEP_REVIEWER,
                        'approval_status' => ApprovalStatus::Pending->value,
                        'mode' => $option->review_mode,
                        'approval_type' => $option->type,
                        'hidden' => true,
                    ];
                    $reviewer_text .= $reviewer['name'] . ', ';
                }
                $checker_text .= ('复审人：(' . trim($reviewer_text, ',') . '）' . ['', '或签', '依次审批'][$option->review_mode]);
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

            if ($insert) $approvalOrder->process()->createMany($insert);

            foreach ($approvalOrder->process as $process) {
                if (!$process->hidden) ApprovalNotifyJob::dispatch($process['user_id'], [
                    'type' => 'approval',
                    'order_id' => $order->id,
                    'process_id' => $process->id,
                    'creator_name' => $process->creator_name,
                    'approval_type' => $approvalOrder->approval_type,
                ]);
            }
        }

        OrderLog::create([
            'order_id' => $order->id,
            'type' => OrderLog::TYPE_FINANCIAL,
            'creator_id' => $user->id,
            'creator_name' => $user->name,
            'creator_company_id' => $user->company_id,
            'creator_company_name' => $order->wusun_company_name,
            'content' => $user->name . '提交付款审核，增加应付款：' . $add_amount . '，备注：' . $checker_text,
            'platform' => \request()->header('platform'),
        ]);

        $order->save();
        return success();
    }

    public function paymentLog(Request $request): JsonResponse
    {
        $records = FinancialPaymentRecord::when($order_id = $request->input('order_id'), function ($query) use ($order_id) {
            $query->where('order_id', $order_id);
        })
            ->when($type = $request->input('financial_type'), function ($query) use ($type) {
                $query->where('financial_type', $type);
            })
            ->when($baoxiao = $request->input('baoxiao'), function ($query) use ($baoxiao) {
                $query->where('baoxiao', $baoxiao);
            })
            ->orderBy('id', 'desc')
            ->get();

        return success($records);
    }

    public function approvalInfo(Request $request): JsonResponse
    {
        if (!$request->input('order_id')) return fail('参数错误');

        $approvalOrder = ApprovalOrder::with('process')
            ->where('order_id', $request->input('order_id'))
            ->where('approval_type', $request->input('approval_type'))
            ->orderBy('id', 'desc')
            ->first();

        $approvalOrder->historys = ApprovalOrderProcess::where('order_id', $request->input('order_id'))
            ->where('approval_status', ApprovalStatus::Rejected->value)
            ->where('history', 1)
            ->where('approval_type', $request->input('approval_type'))
            ->where('approval_order_id', '<', $approvalOrder->id)
            ->orderBy('id', 'desc')
            ->get();

        return success($approvalOrder);
    }
}
