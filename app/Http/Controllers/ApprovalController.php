<?php

namespace App\Http\Controllers;

use App\Models\ApprovalOrder;
use App\Models\ApprovalOrderProcess;
use App\Models\Approver;
use App\Models\BidOption;
use App\Models\Enumerations\ApprovalMode;
use App\Models\Enumerations\ApprovalStatus;
use App\Models\Enumerations\ApprovalType;
use App\Models\Enumerations\Status;
use App\Models\OrderQuotation;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

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
            ->when(explode(',', $request->input('step', '')), function ($query, $step) {
                if (!empty($step)) $query->whereIn('step', $step);
            })
            ->where('hidden', false)
            ->paginate(getPerPage());

        return success($process);
    }

    public function form(Request $request): JsonResponse
    {
        $process = ApprovalOrderProcess::where('approval_order_id', $request->input('process_id'))->first();

        if ($process->approval_status != ApprovalStatus::Pending) return fail('当前状态不可审核');

        $approvalOrder = $process->approvalOrder;

        if ($request->input('type') == 'accept') {
            $process->approval_status = ApprovalStatus::Accepted;
        } else {
            $process->approval_status = ApprovalStatus::Rejected;
        }

        $current_time = now()->toDateTimeString();

        $process->remark = $request->input('remark');
        $process->completed_at = $current_time;
        $process->save();

        $surplus = ApprovalOrderProcess::where('approval_order_id', $process->approval_order_id)
            ->where('approval_status', ApprovalStatus::Pending)->get();

        if (!$surplus) $this->complete($approvalOrder);

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
                    if ($reviewers) $this->startReview($reviewers, $receivers);
                }
            } else {
                if ($reviewers) $this->startReview($reviewers, $receivers);
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
                $this->notifyReceiver($receivers);
            }
        }

        return success();
    }

    /**
     * 进入复核流程
     *
     * @param $reviewers
     * @param $receivers
     * @return void
     */
    protected function startReview($reviewers, $receivers): void
    {
        if ($receivers) {
            $reviewers[0]->hidden = false;
            $reviewers[0]->save();
        } else {
            $this->notifyReceiver($receivers);
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
     * @return void
     */
    protected
    function notifyReceiver($receivers): void
    {
        if ($receivers) {
            $receivers[0]->hidden = false;
            $receivers[0]->save();
        }
    }

    protected function complete(ApprovalOrder $approvalOrder): void
    {
        $approvalOrder->completed_at = now()->toDateTimeString();
        $approvalOrder->save();

        match ($approvalOrder->approval_type) {
            ApprovalType::ApprovalQuotation->value => $this->approvalQuotation($approvalOrder),
            ApprovalType::ApprovalAssessment->value => $this->approvalAssessment($approvalOrder),
        };
    }

    /**
     * 对外报价审批通过（物损公司）；
     *
     * @param ApprovalOrder $approvalOrder
     * @return void
     */
    protected function approvalQuotation(ApprovalOrder $approvalOrder): void
    {
        $order = $approvalOrder->order;

        /**
         * 检查是否首次报价
         */
        if ($order->bid_type == 0 && $approvalOrder->company_id == $order->check_wusun_company_id) {

            $bidOption = BidOption::where('company_id', $approvalOrder->company_id)->where('status', Status::Normal->value)->first();

            $quotation = OrderQuotation::where('order_id', $order->id)->where('company_id', $approvalOrder->company_id)->first();

            // 首次报价低于竞价金额，直接分配工单
            if (!$bidOption or $quotation->total_price < $bidOption->bid_first_price) {
                $order->bid_type = 2;
                $order->wusun_company_id = $approvalOrder->company_id;
                $order->wusun_company_name = $bidOption->company->name;
                $order->confim_wusun_at = now()->toDateTimeString();
            }
        }
    }

    /**
     * 核价定损审批通过（保险公司）；
     *
     * @param ApprovalOrder $approvalOrder
     * @return void
     */
    protected function approvalAssessment(ApprovalOrder $approvalOrder): void
    {

    }
}
