<?php

namespace App\Http\Controllers;

use App\Models\BankAccount;
use App\Models\Company;
use App\Models\FinancialInvoiceRecord;
use App\Models\FinancialOrder;
use App\Models\FinancialPaymentRecord;
use App\Models\Order;
use App\Services\ExportService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class InvoiceController extends Controller
{
    /**
     * 财务列表
     *
     * @param Request $request
     * @return JsonResponse|void
     */
    public function index(Request $request)
    {
        $company = $request->user()->company;
        $company_id = $request->input('company_id');

        $orders = FinancialInvoiceRecord::with('order:id,insurance_company_name')
            ->when($request->get('invoice_type'), fn($query, $type) => $query->where('type', $type))
            ->where(function ($query) use ($company, $company_id) {
                if ($company_id) return $query->where('company_id', $company_id);

                return $query->whereIn('company_id', Company::getGroupId($company->id));
            })
            ->when($request->get('name'), function ($query, $name) {
                $query->where(function ($query) use ($name) {
                    $query->where('order_number', 'like', "%$name%")
                        ->orWhere('case_number', 'like', "%$name%")
                        ->orWhere('license_plate', 'like', "%$name%")
                        ->orWhere('invoice_number', 'like', "%$name%");
                });
            })
            ->when($request->get('insurance_company_id'), fn($query, $value) => $query->where('insurance_company_id', $value))
            ->when($request->get('opposite_company_id'), fn($query, $value) => $query->where('opposite_company_id', $value))
            ->when($request->get('wusun_check_id'), fn($query, $value) => $query->where('wusun_check_id', $value))
            ->when($request->get('invoice_status'), fn($query, $value) => $query->where('invoice_status', $value))
            ->when($request->get('invoice_time_start'), function ($query, $value) {
                $query->where('invoice_time', '>', $value);
            })
            ->when($request->get('invoice_time_end'), function ($query, $value) {
                $query->where('invoice_time', '<=', $value . ' 23:59:59');
            })
            ->when($request->get('payment_time_start'), function ($query, $value) {
                $query->where('payment_time', '>', $value);
            })
            ->when($request->get('payment_time_end'), function ($query, $value) {
                $query->where('payment_time', '<=', $value . ' 23:59:59');
            })
            ->when($request->get('invoice_operator_id'), function ($query, $value) {
                $query->where('invoice_operator_id', $value);
            })
            ->when($request->get('financial_order_id'), function ($query, $value) {
                $query->where('financial_order_id', $value);
            })
            ->when($request->get('financial_type'), function ($query, $value) {
                $query->where('financial_type', $value);
            })
            ->orderBy('id', 'desc');

        if (!$request->input('export')) {
            return success($orders->paginate(getPerPage()));
        }

        $rows = $orders->get()->toArray();

        $headers = ['所属公司', '客户名称', '客户经理', '结算编号', '发票类型', '发票号', '发票金额', '已收款金额', '待收款金额',
            '开票人', '开票时间', '收款人', '收款时间', '开票单位', '物流单位', '快递单号', '开票备注', '收款状态'];

        $result = [];

        foreach ($rows as $row) {
            $result[] = [
                $row['company_name'],
                $row['order']['insurance_company_name'],
                '',
                '',
                ['', '专票', '普票'][(int)$row['invoice_type']], // 发票类型
                $row['invoice_number'], // 发票号
                $row['invoice_amount'], // 发票金额
                $row['paid_amount'], // 已收款金额
                $row['total_amount'] - $row['paid_amount'], // 待收款金额
                $row['invoice_operator_name'], // 开票人
                $row['invoice_time'], // 开票时间
                $row['payment_operator_name'], // 收款人 (收款操作人)
                $row['payment_time'], // 收款时间
                $row['invoice_company_name'], // 开票单位
                $row['express_company_name'], // 物流单位
                $row['express_order_number'], // 快递单号
                $row['invoice_remark'], // 开票备注
                ['', '未收款', '部分收款', '已收款'][(int)$row['payment_status']], // 收款状态
            ];
        }

        $fileName = '开票收款明细表';

        (new ExportService)->excel($headers, $result, $fileName);
    }

    /**
     * 开票
     * @param Request $request
     * @return JsonResponse
     */
    public function invoice(Request $request): JsonResponse
    {
        $user = $request->user();

        $invoiceRecord = $this->initRecord($request);

        $financialOrder = FinancialOrder::find($request->input('financial_order_id'));

        $invoiceRecord->fill($request->only([
            'financial_order_id',
            'invoice_company_name',
            'invoice_type',
            'invoice_tax_rate',
            'invoice_tax_amount',
            'invoice_number',
            'invoice_amount',
            'invoice_time',
            'invoice_images',
            'invoice_remark',
        ]));

        $invoiceRecord->financial_type = $financialOrder->type;
        $invoiceRecord->invoice_operator_id = $user->id;
        $invoiceRecord->invoice_operator_name = $user->name;
        $invoiceRecord->invoice_status = FinancialOrder::STATUS_DONE;

        if ($invoiceRecord->paid_amount == 0) $invoiceRecord->payment_status = FinancialOrder::STATUS_WAIT;
        else $invoiceRecord->payment_status = ($invoiceRecord->paid_amount >= $invoiceRecord->total_amount
            ? FinancialOrder::STATUS_DONE : FinancialOrder::STATUS_PART);

        $financialOrder->invoiced_amount += $invoiceRecord->invoice_amount;
        $financialOrder->invoice_status = ($financialOrder->invoiced_amount >= $financialOrder->total_amount
            ? FinancialOrder::STATUS_DONE : FinancialOrder::STATUS_PART);

        $order = Order::find($financialOrder->order_id);
        $order->invoiced_amount += $invoiceRecord->invoice_amount;

        FinancialPaymentRecord::where('invoice_id', $invoiceRecord->id)->update([
            'invoice_type' => $invoiceRecord->invoice_type,
            'invoice_number' => $invoiceRecord->invoice_number,
            'invoice_amount' => $invoiceRecord->invoice_amount,
            'invoice_company_id' => $invoiceRecord->invoice_company_id,
            'invoice_company_name' => $invoiceRecord->invoice_company_name,
            'invoice_created_at' => now()->toDateTimeString(),
        ]);

        $order->save();
        $financialOrder->save();
        $invoiceRecord->save();

        return success();
    }

    /**
     * 收、付款
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function payment(Request $request): JsonResponse
    {
        $user = $request->user();

        $payment_amount = $request->input('amount');

        $invoiceRecord = $this->initRecord($request);

        $financialOrder = FinancialOrder::find($request->input('financial_order_id'));

        $invoiceRecord->fill($request->only([
            'financial_order_id',
            'payment_images',
            'payment_remark',
        ]));

        $bank_account = BankAccount::find($request->bank_account_id);

        if (!$bank_account) return fail('请选择银行账户');

        $invoiceRecord->financial_type = $financialOrder->type;
        $invoiceRecord->payment_operator_id = $user->id;
        $invoiceRecord->payment_operator_name = $user->name;
        $invoiceRecord->payment_time = $request->input('payment_time');
        $invoiceRecord->paid_amount += $payment_amount;
        $invoiceRecord->payment_status = ($invoiceRecord->paid_amount >= $invoiceRecord->total_amount
            ? FinancialOrder::STATUS_DONE : FinancialOrder::STATUS_PART);

        $financialOrder->paid_amount += $payment_amount;
        $financialOrder->payment_status = ($financialOrder->paid_amount >= $financialOrder->total_amount
            ? FinancialOrder::STATUS_DONE : FinancialOrder::STATUS_PART);

        $order = Order::find($financialOrder->order_id);
        if ($financialOrder->type == FinancialOrder::TYPE_PAYMENT) {
            $order->paid_amount += $payment_amount;
        } else {
            $order->received_amount += $payment_amount;
        }

        $order->save();
        $financialOrder->save();
        $invoiceRecord->save();

        FinancialPaymentRecord::create([
            'invoice_record_id' => $invoiceRecord->id,
            'financial_order_id' => $invoiceRecord->financial_order_id,
            'financial_type' => $invoiceRecord->financial_type,
            'company_id' => $invoiceRecord->company_id,
            'company_name' => $invoiceRecord->company_name,
            'customer_id' => $invoiceRecord->customer_id,
            'customer_name' => $invoiceRecord->customer_name,
            'opposite_company_id' => $invoiceRecord->opposite_company_id,
            'opposite_company_name' => $invoiceRecord->opposite_company_name,
            'order_id' => $invoiceRecord->order_id,
            'order_post_time' => $invoiceRecord->order_post_time,
            'order_number' => $invoiceRecord->order_number,
            'case_number' => $invoiceRecord->case_number,
            'license_plate' => $invoiceRecord->license_plate,
            'bank_account_id' => $request->input('bank_account_id'),
            'bank_name' => $bank_account->bank_name,
            'bank_account_number' => $bank_account->number,
            'his_name' => $financialOrder->payment_name,
            'his_bank_name' => $financialOrder->payment_bank,
            'his_bank_number' => $financialOrder->payment_account,
            'amount' => $request->input('amount'),
            'invoice_id' => $invoiceRecord->id,
            'invoice_type' => $invoiceRecord->invoice_type,
            'invoice_number' => $invoiceRecord->invoice_number,
            'invoice_amount' => $invoiceRecord->invoice_amount,
            'invoice_company_id' => $invoiceRecord->invoice_company_id,
            'invoice_company_name' => $invoiceRecord->invoice_company_name,
            'invoice_created_at' => $invoiceRecord->created_at,
            'operator_id' => $user->id,
            'operator_name' => $user->name,
            'remark' => $invoiceRecord->payment_remark,
            'payment_images' => $invoiceRecord->payment_images,
            'payment_time' => $invoiceRecord->payment_time,
            'baoxiao' => $financialOrder->baoxiao
        ]);

        return success();
    }

    /**
     * 快递
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function express(Request $request): JsonResponse
    {
        $invoiceRecord = FinancialInvoiceRecord::find($request->input('id'));

        if (!$invoiceRecord) return fail('发票记录不存在');

        $user = $request->user();

        $invoiceRecord->fill($request->only([
            'express_company_name',
            'express_order_number',
        ]));

        $invoiceRecord->express_operater_id = $user->id;
        $invoiceRecord->express_operater_name = $user->name;
        $invoiceRecord->express_time = now()->toDateTimeString();
        $invoiceRecord->save();

        return success();
    }

    /**
     * 初始化
     * @param Request $request
     * @return FinancialInvoiceRecord
     */
    protected function initRecord(Request $request): FinancialInvoiceRecord
    {
        return FinancialInvoiceRecord::findOr($request->input('id'), fn() => new FinancialInvoiceRecord(
            FinancialOrder::findAndGetAttrs($request->input('financial_order_id'), [
                'company_id',
                'company_name',
                'order_id',
                'total_amount',
                'order_number',
                'opposite_company_id',
                'opposite_company_name',
                'case_number',
                'order_post_time',
                'license_plate',
            ])
        ));
    }
}
