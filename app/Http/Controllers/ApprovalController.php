<?php

namespace App\Http\Controllers;

use App\Jobs\ApprovalNotifyJob;
use App\Jobs\QuotaBillPdfJob;
use App\Jobs\QuotaHistory;
use App\Models\ApprovalOrder;
use App\Models\ApprovalOrderProcess;
use App\Models\Approver;
use App\Models\Company;
use App\Models\Enumerations\ApprovalMode;
use App\Models\Enumerations\ApprovalStatus;
use App\Models\Enumerations\ApprovalType;
use App\Models\Enumerations\CheckStatus;
use App\Models\Enumerations\MessageType;
use App\Models\Enumerations\OrderCloseStatus;
use App\Models\FinancialOrder;
use App\Models\Message;
use App\Models\Order;
use App\Models\OrderLog;
use App\Models\OrderQuotation;
use App\Models\OrderRepairPlan;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ApprovalController extends Controller
{
    /**
     * 我的审批列表
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function index(Request $request): JsonResponse
    {
        $name = $request->input('name');

        $process = ApprovalOrderProcess::with(['company:id,name', 'order.company:id,name'])
            ->withWhereHas('order', function ($query) use ($name) {
                if ($name) $query->where('order_number', 'like', '%' . $name . '%')
                    ->orWhere('case_number', 'like', '%' . $name . '%')
                    ->orWhere('license_plate', 'like', '%' . $name . '%');
            })
            ->where('user_id', $request->user()->id)
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
            ->orderBy('id', 'desc')
            ->paginate(getPerPage())->toArray();

        $process['count']['All'] = 0;

        foreach (ApprovalType::cases() as $item) {
            $process['count'][$item->name] = ApprovalOrderProcess::where('approval_type', $item->value)
                ->where('user_id', $request->user()->id)
                ->where('approval_status', ApprovalStatus::Pending->value)
                ->whereIn('step', [1, 2])
                ->where('hidden', false)
                ->count();
            $process['count']['All'] += $process['count'][$item->name];
        }

        return success($process);
    }

    /**
     * 审核详情
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function detail(Request $request): JsonResponse
    {
        $company_id = $request->user()->company_id;
        $process = ApprovalOrderProcess::with('company:id,name')
            ->where('id', $request->input('process_id'))->first();

        $withs = ['repair_plan'];

//        if ($process->approval_type == ApprovalType::ApprovalQuotation->value)
        $withs['quotation'] = function ($query) use ($company_id) {
            return $query->where('company_id', $company_id);
        };

        $process->order = Order::with(array_merge(['company:id,name'], $withs))->find($process->order_id);

        $process->approval_list = ApprovalOrderProcess::where('approval_order_id', $process->approval_order_id)->get();

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
            };

            OrderLog::create([
                'order_id' => $approvalOrder->order_id,
                'type' => OrderLog::TYPE_APPROVAL,
                'creator_id' => $user->id,
                'creator_name' => $user->name,
                'creator_company_id' => $user->company_id,
                'creator_company_name' => Company::find($user->company_id)?->name,
                'content' => $user->name . ($accept ? '通过' : '拒绝') . $typeText,
                'platform' => \request()->header('platform'),
            ]);

            if (!$accept) {
                foreach ($surplus as $cancel) {
                    $cancel->approval_status = ApprovalStatus::Canceled;
                    $cancel->save();
                }
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

            if (!app()->environment('prod')) throw $exception;
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
            ]);
        }

        $this->complete($approvalOrder);
    }

    protected function complete(ApprovalOrder $approvalOrder, bool $accept = true): void
    {
        $approvalOrder->completed_at = now()->toDateTimeString();
        $approvalOrder->save();

        match ($approvalOrder->approval_type) {
            ApprovalType::ApprovalQuotation->value => $this->approvalQuotation($approvalOrder, $accept),
            ApprovalType::ApprovalAssessment->value => $this->approvalAssessment($approvalOrder, $accept),
            ApprovalType::ApprovalClose->value => $this->approvalClose($approvalOrder, $accept),
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

        $quotation = OrderQuotation::where('order_id', $order->id)->where('company_id', $approvalOrder->company_id)->first();

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
        ]);

        /**
         * 应付（外协修付）
         */
        $repair_plan = $order->repair_plan;
        if ($repair_plan) {
            if ($repair_plan->repair_type = OrderRepairPlan::TYPE_THIRD_REPAIR) {
                FinancialOrder::createByOrder($order, [
                    'type' => FinancialOrder::TYPE_PAYMENT,
                    'opposite_company_id' => $repair_plan->repair_company_id,
                    'opposite_company_name' => $repair_plan->repair_company_name,
                    'total_amount' => $repair_plan->repair_cost,
                ]);
            }

            foreach ($repair_plan->tasks as $task) {
                FinancialOrder::createByOrder($order, [
                    'type' => FinancialOrder::TYPE_PAYMENT,
                    'opposite_company_id' => $order->insurance_company_id,
                    'opposite_company_name' => Company::find($order->insurance_company_id)?->name,
                    'total_amount' => $order->confirmed_price,
                ]);
            }
        }

        /**
         * 报销 TODO
         */

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
}
