<?php

namespace App\Http\Controllers;

use App\Jobs\BidOpeningJob;
use App\Jobs\QuotaBillPdfJob;
use App\Models\ApprovalOrder;
use App\Models\ApprovalOrderProcess;
use App\Models\Approver;
use App\Models\BidOption;
use App\Models\Enumerations\ApprovalMode;
use App\Models\Enumerations\ApprovalStatus;
use App\Models\Enumerations\ApprovalType;
use App\Models\Enumerations\CheckStatus;
use App\Models\Enumerations\Status;
use App\Models\Order;
use App\Models\OrderQuotation;
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
                $query->where('approval_status', $approval_status);
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
            ->paginate(getPerPage());

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

        $withs = [];

        if ($process->approval_type == ApprovalType::ApprovalQuotation->value)
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

            if (!$surplus) $this->complete($approvalOrder, $accept);

            if (!$accept) {
                foreach ($surplus as $cancel) {
                    $cancel->approval_status = ApprovalStatus::Canceled;
                    $cancel->save();
                }
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
                    } else {
                        $this->completeStep($reviewers);
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
            $reviewers[0]->hidden = false;
            $reviewers[0]->save();
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
        }

        $this->complete($approvalOrder);
    }

    protected function complete(ApprovalOrder $approvalOrder, $accept = true): void
    {
        $approvalOrder->completed_at = now()->toDateTimeString();
        $approvalOrder->save();

        match ($approvalOrder->approval_type) {
            ApprovalType::ApprovalQuotation->value => $this->approvalQuotation($approvalOrder, $accept),
            ApprovalType::ApprovalAssessment->value => $this->approvalAssessment($approvalOrder, $accept),
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

        /**
         * 检查是否首次报价
         */
        if ($order->bid_type == 0 && $approvalOrder->company_id == $order->check_wusun_company_id) {

            $bidOption = BidOption::where('company_id', $approvalOrder->company_id)->where('status', Status::Normal->value)->first();

            // 首次报价低于竞价金额，直接分配工单
            if (!$bidOption or $quotation->total_price < $bidOption->bid_first_price) {
                $order->bid_type = 2;
                $order->wusun_company_id = $approvalOrder->company_id;
                $order->wusun_company_name = $quotation->company->name;
                $order->confim_wusun_at = now()->toDateTimeString();
            } else {
                $now = date('His');

                if ($quotation->total_price < $bidOption->min_goods_price) {
                    if ($now > '083000' && $now < '180000') $duration = $bidOption->working_time_deadline_min;
                    else $duration = $bidOption->resting_time_deadline_min;
                } elseif ($quotation->total_price < $bidOption->mid_goods_price) {
                    if ($now > '083000' && $now < '180000') $duration = $bidOption->working_time_deadline_mid;
                    else $duration = $bidOption->resting_time_deadline_mid;
                } else {
                    if ($now > '083000' && $now < '180000') $duration = $bidOption->working_time_deadline_max;
                    else $duration = $bidOption->resting_time_deadline_max;
                }

                $order->bid_type = 1;
                $order->bid_status = Order::BID_STATUS_PROGRESSING;
                $order->bid_end_time = now()->addHours($duration)->toDateTimeString();
                BidOpeningJob::dispatch($order->id)->delay(now()->addHours($duration));
            }
            $order->save();
        }

        // 生成报价单
        QuotaBillPdfJob::dispatch($quotation);
    }

    /**
     * 核价定损审批通过（保险公司）；
     *
     * @param ApprovalOrder $approvalOrder
     * @param $accept
     * @return void
     */
    protected function approvalAssessment(ApprovalOrder $approvalOrder, $accept): void
    {

    }
}
