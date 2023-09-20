<?php

namespace App\Http\Controllers;

use App\Jobs\ApprovalNotifyJob;
use App\Models\ApprovalOption;
use App\Models\ApprovalOrder;
use App\Models\ApprovalOrderProcess;
use App\Models\Approver;
use App\Models\Enumerations\ApprovalMode;
use App\Models\Enumerations\ApprovalStatus;
use App\Models\Enumerations\ApprovalType;
use App\Models\Order;
use App\Models\OrderLog;
use App\Models\OrderRepairPlan;
use App\Models\RepairQuota;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class OrderRepairController extends Controller
{
    public function detail(Request $request): JsonResponse
    {
        $order_id = $request->input('order_id');

        $plan = OrderRepairPlan::with('tasks')->where('order_id', $order_id)->first();

        return success($plan);
    }

    /**
     * 维修
     * @param Request $request
     * @return JsonResponse
     */
    public function form(Request $request): JsonResponse
    {
        $order_id = $request->input('order_id');

        $order = Order::find($order_id);

        $user = $request->user();

        $company = $user->company;

        $plan = OrderRepairPlan::where('order_id', $order_id)->first();

        $plan->fill($request->only([
            'repair_status',
            'repair_start_at',
            'repair_end_at',
            'cost_images',
            'before_repair_images',
            'repair_images',
            'after_repair_images',
            'repair_remark'
        ]));

        // 复勘审批
        if ($plan->after_repair_images && $plan->isDirty('after_repair_images')) {

            $option = ApprovalOption::findByType($order->insurance_company_id, ApprovalType::ApprovalRepaired->value)
                ?: ApprovalOption::findByType($user->company_id, ApprovalType::ApprovalRepaired->value);

            $checker_text = '';

            if ($option) {
                $approvalOrder = ApprovalOrder::where('order_id', $order->id)->where('approval_type', $option->type)->first();
                if ($approvalOrder) {
                    ApprovalOrderProcess::where('approval_order_id', $approvalOrder->id)->delete();
                    $approvalOrder->delete();
                }

                $approvalOrder = ApprovalOrder::create([
                    'order_id' => $order->id,
                    'company_id' => $option->company_id,
                    'approval_type' => $option->type,
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

                $approvalOrder->process()->delete();
                if ($insert) $approvalOrder->process()->createMany($insert);

                foreach ($approvalOrder->process as $process) {
                    if (!$process->hidden) ApprovalNotifyJob::dispatch($process['user_id'], [
                        'type' => 'approval',
                        'order_id' => $order->id,
                        'process_id' => $process->id,
                        'creator_name' => $process->creator_name,
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
                'content' => $user->name . '提交复勘资料' . '；备注：' . $plan->repair_remark . "审批人：" . $checker_text,
                'platform' => \request()->header('platform'),
            ]);
        }

        $plan->save();

        $order->repair_status = $plan->repair_status;
        $order->save();

        return success();
    }

    /**
     * 回退施工状态
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function rollback(Request $request): JsonResponse
    {
        $order_id = $request->input('order_id');
        $order = Order::find($order_id);
        $plan = OrderRepairPlan::with('tasks')->where('order_id', $order_id)->first();

        if (!$plan) return success();

        if ($plan->repair_status > OrderRepairPlan::REPAIR_STATUS_WAIT) {
            $plan->repair_status -= 1;
            $plan->save();
            $order->repair_status = $plan->repair_status;
            $order->save();
        } else {
            $plan->delete();
            $order->repair_status = Order::REPAIR_STATUS_WAIT;
            $order->repair_company_ids = '';
            $order->save();

            RepairQuota::where('order_id', $order->id)->where('win', 1)->update([
                'win' => 0,
                'updated_at' => now()->toDateTimeString()
            ]);

            RepairQuota::where('order_id', $order->id)->where('quota_type', RepairQuota::TYPE_CHOOSE)->delete();
        }

        return success();
    }
}
