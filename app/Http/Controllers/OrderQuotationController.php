<?php

namespace App\Http\Controllers;

use App\Jobs\ApprovalNotifyJob;
use App\Jobs\BidOpeningJob;
use App\Jobs\QuotaBillPdfJob;
use App\Jobs\QuotaHistory;
use App\Jobs\QuotaMessageJob;
use App\Models\ApprovalOption;
use App\Models\ApprovalOrder;
use App\Models\ApprovalOrderProcess;
use App\Models\Approver;
use App\Models\BidOption;
use App\Models\Company;
use App\Models\CompanyProvider;
use App\Models\Enumerations\ApprovalMode;
use App\Models\Enumerations\ApprovalStatus;
use App\Models\Enumerations\ApprovalType;
use App\Models\Enumerations\CheckStatus;
use App\Models\Enumerations\InsuranceType;
use App\Models\Enumerations\Status;
use App\Models\Order;
use App\Models\OrderLog;
use App\Models\OrderQuotation;
use App\Models\User;
use Carbon\Carbon;
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
        if ($request->user()->hasRole('admin')) return success('超级管理员无法查看');

        $company_id = $request->user()->company_id;
        $company = $request->user()->company;
        $customersId = CompanyProvider::where('provider_id', $company_id)->pluck('company_id');

        $orders = Order::with('company:id,name')
            ->leftJoin('order_quotations as quotation', function ($join) use ($company_id) {
                $join->on('orders.id', '=', 'quotation.order_id')->where('quotation.company_id', $company_id);
            })
            ->where(function ($query) use ($company_id) {
                $query->where('bid_type', 1)
                    ->orWhere('check_wusun_company_id', $company_id);
            })
            ->when($request->input('status'), function ($query, $status) {
                // 1 待报价 2 报价超时 3 未中标 4 已中标
                return match ($status) {
                    '1' => $query->where('bid_status', Order::BID_STATUS_PROGRESSING)->whereNull('quotation.id'),
                    '2' => $query->where('bid_status', Order::BID_STATUS_FINISHED)->whereNull('quotation.id'),
                    '3' => $query->where('bid_status', Order::BID_STATUS_FINISHED)->where('quotation.win', 0),
                    '4' => $query->where('bid_status', Order::BID_STATUS_FINISHED)->where('quotation.win', 1),
                };
            })
            ->when($request->input('name'), function ($query, $name) {
                $query->where('order_number', 'like', '%' . $name . '%')
                    ->orWhere('case_number', 'like', '%' . $name . '%')
                    ->orWhere('license_plate', 'like', '%' . $name . '%');
            })
            ->whereIn('insurance_company_id', $customersId)
            ->when($company->car_part, function ($query) {
                $query->where('insurance_type', InsuranceType::CarPart);
            })
            ->selectRaw('orders.*, quotation.company_id, quotation.plan_type, quotation.repair_days,
             quotation.repair_cost, quotation.other_cost, quotation.total_cost, quotation.profit_margin,
             quotation.bid_created_at,quotation.bid_repair_days,quotation.bid_total_price,
             quotation.profit_margin_ratio, quotation.repair_remark, quotation.total_price, quotation.images,
             quotation.check_status, quotation.checked_at, quotation.win, quotation.submit')
            ->orderBy('orders.id', 'desc')
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
            ->firstOr(fn() => new OrderQuotation([
                'security_code' => Str::random(),
                'company_id' => $user->company_id,
                'company_name' => $user->company->name,
                'creator_id' => $user->id,
                'creator_name' => $user->name
            ]));

        if ($order->bid_type == Order::BID_TYPE_JINGJIA && now()->gt($order->bid_end_time) && !$quotation->id) {
            return fail('报价已截止');
        }

        if ($quotation->company_id == $order->wusun_company_id) $quotation->win = 1;

        $quotation->fill($request->only([
            'order_id',
            'plan_type',
            'repair_days',
            'repair_cost',
            'other_cost',
            'labor_costs',
            'material_cost',
            'total_cost',
            'profit_margin',
            'profit_margin_ratio',
            'repair_remark',
            'total_price',
            'bid_repair_days',
            'bid_total_price',
            'bid_created_at',
            'images',
            'submit',
            'quotation_remark',
            'approve_remark',
            'modify_quotation_remark'
        ]));

        if (
            $quotation->bid_created_at and
            ($quotation->isDirty('bid_repair_days') or $quotation->isDirty('bid_total_price'))
        ) return fail('报价信息不允许修改');

        try {
            DB::beginTransaction();

            if ($quotation->win == 1) {
                $quotation->bid_total_price = $quotation->getOriginal('bid_total_price');
                $quotation->bid_repair_days = $quotation->getOriginal('bid_repair_days');
            }

            if ($quotation->isDirty('bid_total_price') or $quotation->isDirty('bid_repair_days')) {
                $quotation->bid_created_at = now()->toDateTimeString();
                $quotation->save();
                // 对外报价
                OrderLog::create([
                    'order_id' => $order->id,
                    'type' => OrderLog::TYPE_QUOTATION,
                    'creator_id' => $quotation->creator_id,
                    'creator_name' => $quotation->creator_name,
                    'creator_company_id' => $quotation->company_id,
                    'creator_company_name' => $quotation->company_name,
                    'content' => $quotation->creator_name . '对外报价，报价金额为' . $quotation->bid_total_price . '预计施工工期：'
                        . $quotation->bid_repair_days . '天；备注：' . $quotation->quotation_remark,
                    'platform' => \request()->header('platform'),
                ]);
            }

            $quotation->save();

            $quotation->items()->delete();

            $quotation->items()->createMany($request->input('items', []));

            if ($quotation->win && $quotation->submit) {

                if ($quotation->submit_at && $quotation->check_status == CheckStatus::Wait->value)
                    throw new Exception('报价单审核中，请耐心等待审核');

                $quotation->submit_at = now()->toDateTimeString();
                $quotation->check_status = CheckStatus::Wait->value;
                $quotation->pdf = '';

                $option = ApprovalOption::findByType($order->insurance_company_id, ApprovalType::ApprovalQuotation->value)
                    ?: ApprovalOption::findByType($user->company_id, ApprovalType::ApprovalQuotation->value);

                $checker_text = '';

                if (!$option) {
                    $quotation->check_status = CheckStatus::Accept->value;
                    $quotation->checked_at = now()->toDateTimeString();

                    // 生成报价单
                    QuotaBillPdfJob::dispatch($quotation);
                    // 报价数据加入数据库
                    QuotaHistory::dispatch($quotation);
                } else {
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

                    if ($quotation->profit_margin_ratio < $option->review_conditions) {
                        foreach ($reviewers as $reviewer) {
                            $insert[] = [
                                'user_id' => $reviewer['id'],
                                'name' => $reviewer['name'],
                                'creator_id' => $user->id,
                                'creator_name' => $user->name,
                                'order_id' => $order->id,
                                'company_id' => $option->company_id,
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

                    if ($option->approvalExtends) {
                        foreach ($option->approvalExtends as $approvalExtend) {
                            if ($quotation->total_price > $approvalExtend['start']
                                && ($approvalExtend['end'] == 0 || $quotation->total_price <= $approvalExtend['end'])
                            ) {
                                $insert[] = [
                                    'user_id' => $approvalExtend['user_id'],
                                    'name' => User::find($approvalExtend['user_id'])?->name,
                                    'creator_id' => $user->id,
                                    'creator_name' => $user->name,
                                    'order_id' => $order->id,
                                    'company_id' => User::find($approvalExtend['user_id'])?->company_id,
                                    'step' => Approver::STEP_REVIEWER,
                                    'approval_status' => ApprovalStatus::Pending->value,
                                    'mode' => $option->review_mode,
                                    'approval_type' => $option->type,
                                    'hidden' => true,
                                ];
                            }
                        }
                    }

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
                            'approval_type' => $approvalOrder->approval_type,
                        ]);
                    }
                }
                $quotation->save();
                OrderLog::create([
                    'order_id' => $order->id,
                    'type' => OrderLog::TYPE_SUBMIT_QUOTATION,
                    'creator_id' => $quotation->creator_id,
                    'creator_name' => $quotation->creator_name,
                    'creator_company_id' => $quotation->company_id,
                    'creator_company_name' => $quotation->company_name,
                    'content' => $quotation->creator_name . '提交报价审核，报价金额为' . $quotation->total_price . '预计施工工期：'
                        . $quotation->repair_days . '天；备注：' . $quotation->quotation_remark . '；' . $checker_text,
                    'platform' => \request()->header('platform'),
                ]);
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
                $company = Company::find($request->user()->company_id);
                $quotation = new OrderQuotation([
                    'order_id' => $request->input('order_id'),
                    'company_id' => $company->id,
                    'creator_id' => $request->user()->id,
                    'creator_name' => $request->user()->name,
                    'company_name' => $company->name,
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

        return success($quotation->items);
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

        return view('quota.security')
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

            if ($order->confirm_price_status == Order::CONFIRM_PRICE_STATUS_APPROVAL)
                throw new Exception('当前状态审批中，不能进行编辑');
            if ($order->confirm_price_status == Order::CONFIRM_PRICE_STATUS_FINISHED)
                throw new Exception('当前状态已完成，不能进行编辑');

            $order->fill($request->only([
                'confirmed_price', 'confirmed_repair_days', 'confirmed_remark'
            ]));

            $quotation = OrderQuotation::where('order_id', $order->id)->where('win', 1)->first();
            $quotation->profit_margin_ratio = sprintf('%.2f', ($order->confirmed_price - $quotation->total_cost) / $order->confirmed_price);
            $quotation->save();

            $order->confirm_user_id = $user->id;
            $order->confirm_price_status = Order::CONFIRM_PRICE_STATUS_APPROVAL;

            $order->save();

            $option = ApprovalOption::findByType($user->company_id, ApprovalType::ApprovalAssessment->value);
            if (!$option) {
                $order->confirm_price_status = Order::CONFIRM_PRICE_STATUS_FINISHED;
                $order->confirmed_at = now()->toDateTimeString();
                $order->save();
            } else {
                $approvalOrder = ApprovalOrder::where('order_id', $order->id)
                    ->where('approval_type', $option->type)
                    ->where('company_id', $order->wusun_company_id)
                    ->first();
                if ($approvalOrder) ApprovalOrderProcess::where('approval_order_id', $approvalOrder->id)->delete();
                if ($approvalOrder) $approvalOrder->delete();

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
            DB::commit();
        } catch (Exception $exception) {
            DB::rollBack();
            return fail($exception->getMessage());
        }

        return success();
    }
}
