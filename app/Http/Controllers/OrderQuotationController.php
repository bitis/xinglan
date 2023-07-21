<?php

namespace App\Http\Controllers;

use App\Models\ApprovalOption;
use App\Models\ApprovalOrder;
use App\Models\ApprovalOrderProcess;
use App\Models\Approver;
use App\Models\CompanyProvider;
use App\Models\Enumerations\ApprovalMode;
use App\Models\Enumerations\ApprovalStatus;
use App\Models\Enumerations\ApprovalType;
use App\Models\Enumerations\CheckStatus;
use App\Models\Order;
use App\Models\OrderQuotation;
use Exception;
use Illuminate\Contracts\View\View;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use PhpOffice\PhpSpreadsheet\Reader\Xls;
use PhpOffice\PhpSpreadsheet\Reader\Xlsx;

class OrderQuotationController extends Controller
{

    /**
     * 报价大厅
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function index(Request $request): JsonResponse
    {
        if ($request->user()->hasRole('admin')) return success();

        $company_id = $request->user()->company_id;
        $customersId = CompanyProvider::where('provider_id', $company_id)->pluck('company_id');

        $orders = Order::with('company:id,name')
            ->leftJoin('order_quotations as quotation', 'orders.id', '=', 'quotation.order_id')
            ->where(function ($query) use ($company_id) {
                $query->where('bid_type', 1)->orWhere('check_wusun_company_id', $company_id);
            })
            ->when($request->input('status'), function ($query, $status) {
                // 1 待报价 2 报价超时 3 未中标 4 已中标
                return match ($status) {
                    '1' => $query->where('bid_status', 0)->whereNull('quotation.id'),
                    '2' => $query->where('bid_status', 1)->whereNull('quotation.id'),
                    '3' => $query->where('bid_status', 1)->where('quotation.win', 0),
                    '4' => $query->where('bid_status', 1)->where('quotation.win', 1),
                };
            })
            ->when($request->input('name'), function ($query, $name) {
                $query->where('order_number', 'like', '%' . $name . '%')
                    ->orWhere('case_number', 'like', '%' . $name . '%')
                    ->orWhere('license_plate', 'like', '%' . $name . '%');
            })
            ->whereIn('insurance_company_id', $customersId)
            ->selectRaw('orders.*, quotation.company_id, quotation.plan_type, quotation.repair_days,
             quotation.repair_cost, quotation.other_cost, quotation.total_cost, quotation.profit_margin,
             quotation.profit_margin_ratio, quotation.repair_remark, quotation.total_price, quotation.images,
             quotation.check_status, quotation.checked_at, quotation.win, quotation.bid_end_time, quotation.submit')
            ->paginate(getPerPage());

        return success($orders);
    }

    /**
     * 获取当前公司某工单的报价详情 （物损公司）
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function getByOrderId(Request $request): JsonResponse
    {
        $quotation = OrderQuotation::where('company_id', $request->user()->company_id)
            ->where('order_id', $request->input('order_id'))->first();

        return success($quotation);
    }

    /**
     * 提交报价（物损公司）
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function form(Request $request): JsonResponse
    {
        $user = $request->user();

        $order = Order::find($request->input('order_id'));

        $quotation = OrderQuotation::where('order_id', $request->input('order_id'))
            ->where('company_id', $user->company_id)
            ->firstOr(fn() => new OrderQuotation(['security_code' => Str::random()]));

        $quotation->fill($request->only([
            'order_id',
            'plan_type',
            'repair_days',
            'repair_cost',
            'other_cost',
            'total_cost',
            'profit_margin',
            'profit_margin_ratio',
            'repair_remark',
            'total_price',
            'images',
            'submit',
            'quotation_remark',
            'modify_quotation_remark'
        ]));

        try {
            DB::beginTransaction();

            if ($quotation->submit) {
                $option = ApprovalOption::findByType($user->company_id, ApprovalType::ApprovalQuotation->value);
            }

            $quotation->company_id = $user->company_id;

            $quotation->save();

            $quotation->items()->delete();

            $quotation->items()->createMany($request->input('items', []));

            if ($quotation->submit) {

                ApprovalOrder::where('order_id', $order->id)->where('company_id', $quotation->company_id)->delete();
                ApprovalOrderProcess::where('order_id', $order->id)->where('company_id', $quotation->company_id)->delete();

                $approvalOrder = ApprovalOrder::create([
                    'order_id' => $order->id,
                    'company_id' => $quotation->company_id,
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
                        'company_id' => $quotation->company_id,
                        'step' => Approver::STEP_CHECKER,
                        'approval_status' => ApprovalStatus::Pending->value,
                        'mode' => $option->approve_mode,
                        'approval_type' => $option->type,
                        'hidden' => $index > 0 && $option->approve_mode == ApprovalMode::QUEUE->value,
                    ];
                }

                if ($quotation->profit_margin_ratio < $option->review_conditions) {
                    foreach ($reviewers as $reviewer) {
                        $insert[] = [
                            'user_id' => $reviewer['id'],
                            'name' => $reviewer['name'],
                            'creator_id' => $user->id,
                            'creator_name' => $user->name,
                            'order_id' => $order->id,
                            'company_id' => $quotation->company_id,
                            'step' => Approver::STEP_REVIEWER,
                            'approval_status' => ApprovalStatus::Pending->value,
                            'mode' => $option->review_mode,
                            'approval_type' => $option->type,
                            'hidden' => true,
                        ];
                    }
                }

                foreach ($receivers as $receiver) {
                    $insert[] = [
                        'user_id' => $receiver['id'],
                        'name' => $receiver['name'],
                        'creator_id' => $user->id,
                        'creator_name' => $user->name,
                        'order_id' => $order->id,
                        'company_id' => $quotation->company_id,
                        'step' => Approver::STEP_RECEIVER,
                        'approval_status' => ApprovalStatus::Pending->value,
                        'mode' => ApprovalMode::QUEUE->value,
                        'approval_type' => $option->type,
                        'hidden' => true,
                    ];
                }

                $approvalOrder->process()->delete();
                if ($insert) $approvalOrder->process()->createMany($insert);
            }
            DB::commit();
        } catch (Exception $exception) {
            DB::rollBack();
            return fail($exception->getMessage());
        }

        return success();
    }

    /**
     * 导入报价明细
     * @param Request $request
     * @return JsonResponse
     */
    public function import(Request $request): JsonResponse
    {
        $file = $request->file('file');

        $extension = strtolower($file->extension());

        if ($extension !== 'xlsx' && $extension !== 'xls') {
            return fail('文件格式不正确');
        }

        try {
            $quotation = OrderQuotation::where('order_id', $request->input('order_id'))
                ->where('company_id', $request->user()->company_id)
                ->first();

            if (!$quotation) {
                $quotation = new OrderQuotation([
                    'order_id' => $request->input('order_id'),
                    'company_id' => $request->user()->company_id,
                ]);
                $quotation->save();
            }

            $reader = match ($extension) {
                'xlsx' => new Xlsx(),
                'xls' => new Xls(),
            };

            $items = [];
            $sheet = $reader->load($file->getRealPath())->getSheet(0)->toArray();
            foreach ($sheet as $index => $row) {
                if ($index === 0) continue;
                $items[] = [
                    'order_quotation_id' => $quotation->id,
                    'sort_num' => $index,
                    'name' => $row[0],
                    'specs' => $row[1],
                    'unit' => $row[2],
                    'number' => $row[3],
                    'price' => $row[4],
                    'total_price' => $row[5],
                    'remark' => $row[6],
                ];
            }

            $quotation->items()->delete();

            $quotation->items()->createMany($items);
        } catch (Exception $e) {
            return fail($e->getMessage());
        }

        return success();
    }

    /**
     * 生成报价单
     *
     * @param string $code
     * @return View
     */
    public function getBySecurityCode(string $code): View
    {
        $quotation = OrderQuotation::with(['company', 'order', 'order.company'])->where('security_code', $code)->first();

        return view('quota.table')
            ->with(compact('quotation'));
    }

    /**
     * 保险公司核价、物损公司定损
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function confirm(Request $request): JsonResponse
    {
        $user = $request->user();

        $order = Order::where('id', $request->input('order_id'))->first();

        if (!$order) return fail('订单不存在');

        try {
            DB::beginTransaction();

            $option = ApprovalOption::findByType($user->company_id, ApprovalType::ApprovalAssessment->value);

            if ($order->confirmed_check_status == CheckStatus::Accept->value)
                throw new Exception('已审核通过，不允许定损价格');

            $order->fill($request->only([
                'confirmed_price', 'confirmed_repair_days', 'confirmed_remark'
            ]));

            $order->confirm_user_id = $user->id;

            $order->save();

            ApprovalOrder::where('order_id', $order->id)->where('company_id', $order->wusun_company_id)->delete();
            ApprovalOrderProcess::where('order_id', $order->id)->where('company_id', $order->wusun_company_id)->delete();

            $approvalOrder = ApprovalOrder::create([
                'order_id' => $order->id,
                'company_id' => $order->wusun_company_id,
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
        } catch (Exception $exception) {
            DB::rollBack();
            return fail($exception->getMessage());
        }

        return success();
    }
}
