<?php

namespace App\Http\Controllers;

use App\Jobs\ApprovalNotifyJob;
use App\Jobs\QuotaBillPdfJob;
use App\Jobs\QuotaHistory;
use App\Models\ApprovalLog;
use App\Models\ApprovalOrder;
use App\Models\ApprovalOrderProcess;
use App\Models\Approver;
use App\Models\Company;
use App\Models\Enumerations\ApprovalMode;
use App\Models\Enumerations\ApprovalStatus;
use App\Models\Enumerations\ApprovalType;
use App\Models\Enumerations\CheckStatus;
use App\Models\Enumerations\CompanyType;
use App\Models\Enumerations\MessageType;
use App\Models\Enumerations\OrderCloseStatus;
use App\Models\FinancialOrder;
use App\Models\Message;
use App\Models\Order;
use App\Models\OrderLog;
use App\Models\OrderQuotation;
use App\Models\OrderRepairPlan;
use App\Services\ExportService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ApprovalController extends Controller
{
    /**
     * 我的审批列表
     *
     * @param Request $request
     * @return JsonResponse|void
     */
    public function index(Request $request)
    {
        $name = $request->input('name');

        $result = ApprovalOrderProcess::with([
            'company:id,name,city',
            'order:id,order_number',
            'order.company:id,name',
            'order.quotation:id,order_id,total_price'
        ])
            ->withWhereHas('order', function ($query) use ($name) {
                if ($name) $query->where('order_number', 'like', '%' . $name . '%')
                    ->orWhere('case_number', 'like', '%' . $name . '%')
                    ->orWhere('license_plate', 'like', '%' . $name . '%');
            })
            ->where('user_id', $request->user()->id)
            ->when($request->get('completed_at_start'), function ($query, $completed_at_start) {
                $query->where('completed_at', '>', $completed_at_start);
            })
            ->when($request->get('completed_at_end'), function ($query, $completed_at_end) {
                $query->where('completed_at', '<=', $completed_at_end . ' 23:59:59');
            })
            ->when(strlen($approval_status = $request->input('approval_status')), function ($query) use ($approval_status) {
                $query->whereIn('approval_status', explode(',', $approval_status));
            })
            ->when($request->input('company_id'), function ($query, $company_id) {
                $query->where('company_id', $company_id);
            })
            ->when($request->input('approval_type'), function ($query, $approval_type) {
                $query->where('approval_type', $approval_type);
            })
            ->when($request->input('step', ''), function ($query, $step) {
                if ($step) $query->whereIn('step', explode(',', $step));
            })
            ->where('hidden', false)
            ->orderBy('approval_status')
            ->orderBy('completed_at', 'desc')
            ->orderBy('id', 'desc');

        if (empty($request->input('export'))) {
            $result = $result->paginate(getPerPage())->toArray();
            $result['count']['All'] = 0;

            foreach (ApprovalType::cases() as $item) {
                $result['count'][$item->name] = ApprovalOrderProcess::where('approval_type', $item->value)
                    ->where('user_id', $request->user()->id)
                    ->where('approval_status', ApprovalStatus::Pending->value)
                    ->whereIn('step', [1, 2])
                    ->where('hidden', false)
                    ->count();
                $result['count']['All'] += $result['count'][$item->name];
            }
            return success($result);
        }

        $headers = ['序号', '地州名称', '提交日期', '报案号', '报价金额', '审核金额', '审减率', '保险公司名称', '提报人', '期间',
            '期间1', '受损物品名称', '受损物品类别', '车牌号', '报价结果'];

        $rows = $result->get()->toArray();

        $result = [];
        $week = function ($date) {
            $firstDayOfMonth = date('Y-m-01', strtotime($date));
            $lastDayOfMonth = date('Y-m-t', strtotime($date));
            $firstDayOfWeek = date('N', strtotime($firstDayOfMonth));
            $lastDayOfWeek = date('N', strtotime($lastDayOfMonth));
            $week = ceil((date('j', strtotime($date)) + $firstDayOfWeek - 1) / 7);

            if ($week > 4 && date('j', strtotime($date)) + $lastDayOfWeek > date('t', strtotime($date))) {
                $week--;
            }
            return date('Y年m月', strtotime($date)) . '第' . $week . '周';
        };

        foreach ($rows as $index => $row) {

            $result[] = [
                $index,
                $row['company']['city'], // 公司所在市
                $row['created_at'],
                $row['order']['case_number'], // 报案号
                empty($row['order']['quotation']) ? '' : $row['order']['quotation']['total_price'], // 报价金额
                '', // 审核金额
                '', // 审减率
                $row['order']['insurance_company_name'], // 保险公司名称
                $row['creator_name'], // 提报人,
                $week($row['created_at']), // 2022年8月第4周
                substr($row['created_at'], '0', '10'), // 期间1
                $row['order']['goods_name'],
                $row['order']['goods_types'],
                $row['order']['license_plate'],
                ['', '施工修复', '协调处理'][(int)$row['order']['plan_type']]
            ];
        }

        $fileName = 'OA已处理';

        (new ExportService)->excel($headers, $result, $fileName);
    }

    /**
     * 审核详情
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function detail(Request $request): JsonResponse
    {
        $company = $request->user()->company;

        $process = ApprovalOrderProcess::with('company:id,name')
            ->where('id', $request->input('process_id'))->first();

        $withs = ['repair_plan'];

//        if ($process->approval_type == ApprovalType::ApprovalQuotation->value)
        $withs['quotation'] = function ($query) use ($company) {
            if ($company->getRawOriginal('type') == CompanyType::BaoXian->value)
                return $query->where('win', 1);
            $children = Company::getGroupId($company->id);

            return $query->whereIn('company_id', $children);
        };

        $process->order = Order::with(array_merge(['company:id,name'], $withs))->find($process->order_id);

        if ($company->type == CompanyType::WuSun->value) {
            $process->financial_orders = FinancialOrder::where('order_id', $process->order_id)
                ->where('type', 2)->whereIn('check_status', [0, 1])->get();
        }

        $process->approval_list = ApprovalOrderProcess::where('approval_order_id', $process->approval_order_id)->get();

        $process->approval_logs = ApprovalLog::where([
            'status' => 0,
            'order_id' => $process->order_id,
            'type' => $process->approval_type
        ])->orderBy('id', 'desc')->get();

        return success($process);
    }

    /**
     * 审批操作
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function form(Request $request): JsonResponse
    {
        $process = ApprovalOrderProcess::where('id', $request->input('process_id'))->first();

        if ($process->approval_status != ApprovalStatus::Pending->value) return fail('当前状态不可审核');

        $user = $request->user();

        $approvalOrder = $process->approvalOrder;

        try {
            DB::beginTransaction();

            if ($request->input('type') == 'accept') {
                $process->approval_status = ApprovalStatus::Accepted;
            } else {
                $process->approval_status = ApprovalStatus::Rejected;
            }

            $accept = $process->approval_status == ApprovalStatus::Accepted;

            $current_time = now()->toDateTimeString();

            $process->remark = $request->input('remark');
            $process->completed_at = $current_time;
            $process->save();

            $surplus = ApprovalOrderProcess::where('approval_order_id', $process->approval_order_id)
                ->where('approval_status', ApprovalStatus::Pending->value)
                ->get();

            $typeText = match ($approvalOrder->approval_type) {
                ApprovalType::ApprovalQuotation->value => '对外报价审核',
                ApprovalType::ApprovalAssessment->value => '核价（定损）审核',
                ApprovalType::ApprovalClose->value => '关闭工单审核',
                ApprovalType::ApprovalRepairCost->value => '施工修复成本审核',
                ApprovalType::ApprovalRepaired->value => '已修复资料审核',
                ApprovalType::ApprovalPayment->value => '付款审核',
            };

            OrderLog::create([
                'order_id' => $approvalOrder->order_id,
                'type' => OrderLog::TYPE_APPROVAL,
                'creator_id' => $user->id,
                'creator_name' => $user->name,
                'creator_company_id' => $user->company_id,
                'creator_company_name' => Company::find($user->company_id)?->name,
                'content' => $user->name . ($accept ? '通过' : '拒绝') . $typeText . "，备注：" . $process->remark,
                'platform' => \request()->header('platform'),
            ]);

            ApprovalLog::create([
                'order_id' => $approvalOrder->order_id,
                'type' => $approvalOrder->approval_type,
                'status' => $accept,
                'remark' => $process->remark,
                'user_id' => $user->id,
                'user_name' => $user->name,
            ]);

            if (!$accept) {
                foreach ($surplus as $cancel) {
                    $cancel->approval_status = ApprovalStatus::Canceled;
                    $cancel->save();
                }
                $order = Order::find($approvalOrder->order_id);
                // Message
                $message = new Message([
                    'send_company_id' => $user->company_id,
                    'to_company_id' => $user->company_id,
                    'user_id' => $process->creator_id,
                    'type' => MessageType::AppraisalReject->value,
                    'order_id' => $approvalOrder->order_id,
                    'order_number' => $order->order_number,
                    'case_number' => $order->case_number,
                    'goods_types' => $order->goods_types,
                    'remark' => $process->remark,
                    'status' => 0,
                    'appraisal_type' => $typeText,
                    'appraisal_status' => $accept,
                ]);

                $message->save();

                $this->complete($approvalOrder, false);
                DB::commit();
                return success();
            }

            $checkers = [];
            $reviewers = [];
            $receivers = [];

            foreach ($surplus as $item) {
                if ($item->step == Approver::STEP_CHECKER) {
                    $checkers[] = $item;
                } elseif ($item->step == Approver::STEP_REVIEWER) {
                    $reviewers[] = $item;
                } elseif ($item->step == Approver::TYPE_RECEIVER) {
                    $receivers[] = $item;
                }
            }

            if ($process->step == Approver::STEP_CHECKER) {
                if ($checkers) {
                    if ($process->mode == ApprovalMode::QUEUE->value) {
                        $checkers[0]->hidden = false;
                        $checkers[0]->save();
                        ApprovalNotifyJob::dispatch($checkers[0]->user_id, [
                            'type' => 'approval',
                            'order_id' => $checkers[0]->order_id,
                            'process_id' => $checkers[0]->id,
                            'creator_name' => $checkers[0]->creator_name,
                            'approval_type' => $approvalOrder->approval_type,
                        ]);
                    } else {
                        $this->completeStep($checkers);
                        $this->startReview($reviewers, $receivers, $approvalOrder);
                    }
                } else {
                    $this->startReview($reviewers, $receivers, $approvalOrder);
                }
            }

            if ($process->step == Approver::STEP_REVIEWER) {
                if ($reviewers) {
                    if ($process->mode == ApprovalMode::QUEUE->value) {
                        $reviewers[0]->hidden = false;
                        $reviewers[0]->save();
                        ApprovalNotifyJob::dispatch($reviewers[0]->user_id, [
                            'type' => 'approval',
                            'order_id' => $reviewers[0]->order_id,
                            'process_id' => $reviewers[0]->id,
                            'creator_name' => $reviewers[0]->creator_name,
                            'approval_type' => $approvalOrder->approval_type,
                        ]);
                    } else {
                        $this->completeStep($reviewers);
                        $this->notifyReceiver($receivers, $approvalOrder);
                    }
                } else {
                    $this->notifyReceiver($receivers, $approvalOrder);
                }
            }

            DB::commit();
        } catch (\Exception $exception) {
            DB::rollBack();

            return fail($exception->getMessage());
        }

        return success();
    }

    /**
     * 进入复核流程
     *
     * @param $reviewers
     * @param $receivers
     * @param $approvalOrder
     * @return void
     */
    protected function startReview($reviewers, $receivers, $approvalOrder): void
    {
        if ($reviewers) {
            if ($reviewers[0]->mode == ApprovalMode::QUEUE->value) {
                $reviewers[0]->hidden = false;
                $reviewers[0]->save();
                ApprovalNotifyJob::dispatch($reviewers[0]->user_id, [
                    'type' => 'approval',
                    'order_id' => $reviewers[0]->order_id,
                    'process_id' => $reviewers[0]->id,
                    'creator_name' => $reviewers[0]->creator_name,
                    'approval_type' => $approvalOrder->approval_type,
                ]);
            } else {
                foreach ($reviewers as $reviewer) {
                    $reviewer->hidden = false;
                    $reviewer->save();
                    ApprovalNotifyJob::dispatch($reviewer->user_id, [
                        'type' => 'approval',
                        'order_id' => $reviewer->order_id,
                        'process_id' => $reviewer->id,
                        'creator_name' => $reviewer->creator_name,
                        'approval_type' => $approvalOrder->approval_type,
                    ]);
                }
            }
        } else {
            $this->notifyReceiver($receivers, $approvalOrder);
        }
    }

    /**
     * 结束一个流程
     *
     * @param $process
     * @return void
     */
    protected function completeStep($process): void
    {
        foreach ($process as $item) {
            $item->hidden = true;
            $item->approval_status = ApprovalStatus::Accepted->value;
            $item->completed_at = now()->toDateTimeString();
            $item->save();
        }
    }

    /**
     * 通知抄送人
     *
     * @param $receivers
     * @param $approvalOrder
     * @return void
     */
    protected function notifyReceiver($receivers, $approvalOrder): void
    {
        foreach ($receivers as $receiver) {
            $receiver->hidden = false;
            $receiver->save();
            ApprovalNotifyJob::dispatch($receiver->user_id, [
                'type' => 'approval',
                'order_id' => $receiver->order_id,
                'process_id' => $receiver->id,
                'creator_name' => $receiver->creator_name,
                'approval_type' => $approvalOrder->approval_type,
            ]);
        }

        $this->complete($approvalOrder);
    }

    /**
     * 结束审批
     *
     * @param ApprovalOrder $approvalOrder
     * @param bool $accept
     * @return void
     */
    protected function complete(ApprovalOrder $approvalOrder, bool $accept = true): void
    {
        $approvalOrder->completed_at = now()->toDateTimeString();
        $approvalOrder->save();

        match ($approvalOrder->approval_type) {
            ApprovalType::ApprovalQuotation->value => $this->approvalQuotation($approvalOrder, $accept),
            ApprovalType::ApprovalAssessment->value => $this->approvalAssessment($approvalOrder, $accept),
            ApprovalType::ApprovalClose->value => $this->approvalClose($approvalOrder, $accept),
            ApprovalType::ApprovalRepairCost->value => $this->approvalRepairCost($approvalOrder, $accept),
            ApprovalType::ApprovalRepaired->value => $this->approvalRepaired($approvalOrder, $accept),
            ApprovalType::ApprovalPayment->value => $this->approvalPayment($approvalOrder, $accept),

        };
    }

    /**
     * 对外报价审批通过（物损公司）；
     *
     * @param ApprovalOrder $approvalOrder
     * @param $accept
     * @return void
     */
    protected function approvalQuotation(ApprovalOrder $approvalOrder, $accept): void
    {
        $order = $approvalOrder->order;

        $quotation = OrderQuotation::where('order_id', $order->id)->where('win', 1)->first();

        $quotation->check_status = $accept ? CheckStatus::Accept->value : CheckStatus::Reject->value;
        $quotation->checked_at = now()->toDateTimeString();
        $quotation->submit = $accept ? 1 : 0;
        $quotation->save();

        if (!$accept) return;

        // 生成报价单
        QuotaBillPdfJob::dispatch($quotation);
        // 报价数据加入数据库
        QuotaHistory::dispatch($quotation);
    }

    /**
     * 核价/定损审批通过；
     *
     * @param ApprovalOrder $approvalOrder
     * @param $accept
     * @return void
     */
    protected function approvalAssessment(ApprovalOrder $approvalOrder, $accept): void
    {
        $order = $approvalOrder->order;

        /**
         * 应收
         */
        $order->receivable_count = $order->confirmed_price;

        FinancialOrder::createByOrder($order, [
            'type' => FinancialOrder::TYPE_RECEIPT,
            'opposite_company_id' => $order->insurance_company_id,
            'opposite_company_name' => Company::find($order->insurance_company_id)?->name,
            'total_amount' => $order->confirmed_price,
            'check_status' => 1,
            'checked_at' => now()->toDateTimeString(),
        ]);

        /**
         * 应付（外协修付）
         */
//        $repair_plan = $order->repair_plan;
//        if ($repair_plan) {
//            if ($repair_plan->repair_type = OrderRepairPlan::TYPE_THIRD_REPAIR) {
//                FinancialOrder::createByOrder($order, [
//                    'type' => FinancialOrder::TYPE_PAYMENT,
//                    'opposite_company_id' => $repair_plan->repair_company_id,
//                    'opposite_company_name' => $repair_plan->repair_company_name,
//                    'total_amount' => $repair_plan->repair_cost,
//                ]);
//            }
//
//            foreach ($repair_plan->tasks as $task) {
//                FinancialOrder::createByOrder($order, [
//                    'type' => FinancialOrder::TYPE_PAYMENT,
//                    'opposite_company_id' => $order->insurance_company_id,
//                    'opposite_company_name' => Company::find($order->insurance_company_id)?->name,
//                    'total_amount' => $order->confirmed_price,
//                ]);
//            }
//        }

        $order->confirm_price_status = $accept ? Order::CONFIRM_PRICE_STATUS_FINISHED : Order::CONFIRM_PRICE_STATUS_WAIT;
        $order->confirmed_at = now()->toDateTimeString();
        $order->save();

        // Message
        $message = new Message([
            'send_company_id' => $order->insurance_company_id,
            'to_company_id' => $order->wusun_company_id,
            'type' => MessageType::ConfirmedPrice->value,
            'order_id' => $order->id,
            'order_number' => $order->order_number,
            'case_number' => $order->case_number,
            'goods_types' => $order->goods_types,
            'remark' => $order->confirmed_remark,
            'status' => 0,
        ]);

        $message->save();
    }

    /**
     * 结案审核
     *
     * @param ApprovalOrder $approvalOrder
     * @param bool $accept
     * @return void
     */
    private function approvalClose(ApprovalOrder $approvalOrder, bool $accept): void
    {
        $order = $approvalOrder->order;

        $order->close_status = $accept ? OrderCloseStatus::Closed->value : OrderCloseStatus::Wait->value;
        $order->close_at = $accept ? now()->toDateTimeString() : null;
        $order->save();

        // Message
        $message = new Message([
            'send_company_id' => $order->wusun_company_id,
            'to_company_id' => $order->wusun_company_id,
            'type' => MessageType::OrderClosed->value,
            'order_id' => $order->id,
            'order_number' => $order->order_number,
            'case_number' => $order->case_number,
            'goods_types' => $order->goods_types,
            'remark' => $order->close_remark,
            'status' => 0,
        ]);

        $message->save();
    }


    /**
     * 施工修复成本审核
     *
     * @param ApprovalOrder $approvalOrder
     * @param bool $accept
     * @return void
     */
    private function approvalRepairCost(ApprovalOrder $approvalOrder, bool $accept)
    {
        $order = $approvalOrder->order;

        $quotation = OrderQuotation::where('order_id', $order->id)
            ->where('company_id', $approvalOrder->company_id)
            ->first();

        $order->cost_check_status = $accept ? Order::COST_CHECK_STATUS_PASS : Order::COST_CHECK_STATUS_WAIT;
        $order->cost_checked_at = $accept ? now()->toDateTimeString() : null;

        $order->profit_margin_ratio = 0;
        if ($order->bid_total_price > 0)
            $order->profit_margin_ratio = ($quotation->bid_total_price - $order->total_cost) / $quotation->bid_total_price;
        $order->save();

        if ($quotation) $quotation->save();

        // Message
        $message = new Message([
            'send_company_id' => $order->wusun_company_id,
            'to_company_id' => $order->wusun_company_id,
            'type' => MessageType::ConfirmedCost,
            'order_id' => $order->id,
            'order_number' => $order->order_number,
            'case_number' => $order->case_number,
            'goods_types' => $order->goods_types,
            'remark' => $order->close_remark,
            'status' => 0,
        ]);

        $message->save();
    }

    /**
     * 已修复资料审核
     *
     * @param ApprovalOrder $approvalOrder
     * @param bool $accept
     * @return void
     */
    private function approvalRepaired(ApprovalOrder $approvalOrder, bool $accept): void
    {
        $order = $approvalOrder->order;

        if ($accept) {
            $order->review_at = now()->toDateTimeString();
            $order->save();
        }

        // Message
        $message = new Message([
            'send_company_id' => $order->wusun_company_id,
            'to_company_id' => $order->wusun_company_id,
            'type' => MessageType::Repaired,
            'order_id' => $order->id,
            'order_number' => $order->order_number,
            'case_number' => $order->case_number,
            'goods_types' => $order->goods_types,
            'remark' => $order->close_remark,
            'status' => 0,
        ]);

        $message->save();
    }

    /**
     * 报销支付审核
     *
     * @param ApprovalOrder $approvalOrder
     * @param bool $accept
     * @return void
     */
    private function approvalPayment(ApprovalOrder $approvalOrder, bool $accept): void
    {
        $order = $approvalOrder->order;
        $financialOrders = FinancialOrder::where('order_id', $order->id)
            ->where('company_id', $approvalOrder->company_id)
            ->get();
        foreach ($financialOrders as $financialOrder) {
            $financialOrder->check_status = $accept ? 1 : 2;
            $financialOrder->checked_at = now()->toDateTimeString();
            $financialOrder->save();
            if ($accept) {
                $order->payable_count += $financialOrder['total_amount'];
            }
        }

        if ($order->isDirty('payable_count')) $order->save();
    }
}
