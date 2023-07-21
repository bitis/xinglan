<?php

namespace App\Http\Controllers;

use App\Models\ApprovalOption;
use App\Models\ApprovalOrder;
use App\Models\ApprovalOrderProcess;
use App\Models\Approver;
use App\Models\Company;
use App\Models\Enumerations\ApprovalMode;
use App\Models\Enumerations\ApprovalStatus;
use App\Models\Enumerations\ApprovalType;
use App\Models\Enumerations\CheckStatus;
use App\Models\Enumerations\CompanyType;
use App\Models\Order;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

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
            ->where('bid_type', '>', 0)
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
     * 核价（保险公司）
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function confirm(Request $request): JsonResponse
    {
        $user = $request->user();

        $order = Order::with(['company:id,name', 'quotations'])->find($request->input('order_id'));

        if (!$order) return fail('订单不存在');

        if ($order->confirmed_at) return fail('订单已核价');

        if ($order->bid_status != 1) return fail('该订单暂不能核价');

        if ($order->confirmed_price && $order->confirmed_check_status != CheckStatus::Reject->value)
            return fail('当前状态不能修改核价内容');

        try {
            $option = ApprovalOption::findByType($order->insurance_company_id, ApprovalType::ApprovalAssessment->value);

            DB::beginTransaction();

            $order->fill($request->only([
                'confirmed_price',
                'confirmed_repair_days',
                'confirmed_remark',
            ]));

            $order->save();

            $approvers = $option->approver;

            ApprovalOrder::where('order_id', $order->id)->where('company_id', $order->insurance_company_id)->delete();
            ApprovalOrderProcess::where('order_id', $order->id)->where('company_id', $order->insurance_company_id)->delete();

            $approvalOrder = ApprovalOrder::create([
                'order_id' => $order->id,
                'company_id' => $order->insurance_company_id,
                'approval_type' => $option->type,
            ]);

            $checkers = [];
            $receivers = [];

            foreach ($approvers as $approver) {
                if ($approver->pivot->type == Approver::TYPE_CHECKER) {
                    $checkers[] = ['id' => $approver['id'], 'name' => $approver['name']];
                } elseif ($approver->pivot->type == Approver::TYPE_RECEIVER) {
                    $receivers[] = ['id' => $approver['id'], 'name' => $approver['name']];
                }
            }

            $insert = [];
            foreach ($checkers as $index => $checker) {
                $insert[] = [
                    'user_id' => $checker['id'],
                    'name' => $checker['name'],
                    'creator_id' => $user->id,
                    'creator_name' => $user->name,
                    'order_id' => $order->id,
                    'company_id' => $order->insurance_company_id,
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
                    'company_id' => $order->insurance_company_id,
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
}
