<?php

namespace App\Http\Controllers;

use App\Models\Company;
use App\Models\FinancialOrder;
use App\Models\FinancialPaymentRecord;
use App\Services\ExportService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class FinancialController extends Controller
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

        $orders = FinancialOrder::with('order:id,insurance_company_name,case_number,province,city,area,address,post_time,license_plate,insurance_check_name,insurance_check_phone,wusun_check_name,order_number')
            ->when($request->get('type'), fn($query, $type) => $query->where('type', $type))
            ->where(function ($query) use ($company, $company_id) {
                if ($company_id) return $query->whereIn('company_id', explode(',', $company_id));

                return $query->whereIn('company_id', Company::getGroupId($company->id));
            })
            ->when($request->get('name'), function ($query, $name) {
                $query->where(function ($query) use ($name) {
                    $query->where('order_number', 'like', "%$name%")
                        ->orWhere('case_number', 'like', "%$name%")
                        ->orWhere('license_plate', 'like', "%$name%")
                        ->orWhere('vin', 'like', "%$name%");
                });
            })
            ->when($request->get('insurance_company_id'), fn($query, $value) => $query->whereIn('insurance_company_id', explode(',', $value)))
            ->when($request->get('opposite_company_id'), fn($query, $value) => $query->where('opposite_company_id', $value))
            ->when($request->get('wusun_check_id'), fn($query, $value) => $query->where('wusun_check_id', $value))
            ->when($request->get('payment_status'), fn($query, $value) => $query->whereIn('payment_status', explode(',', $value)))
            ->when($request->get('invoice_status'), fn($query, $value) => $query->whereIn('invoice_status', explode(',', $value)))
            ->when($request->get('post_time_start'), function ($query, $post_time_start) {
                $query->where('post_time', '>', $post_time_start);
            })
            ->when($request->get('post_time_end'), function ($query, $post_time_end) {
                $query->where('post_time', '<=', $post_time_end . ' 23:59:59');
            })
            ->where('check_status', 1)
            ->orderBy('id', 'desc');

        if (!$request->input('export')) {
            return success($orders->paginate(getPerPage()));
        }

        $fileName = '结算明细表';

        $headers = ['所属公司', '客户', '报案号', '物损地点', '接案时间', '标的车车牌', '保险查勘人员', '电话', '审核人员',
            '物损查勘人员', '审核时间', '工单号', '审核金额', '结算金额', '已开票金额', '已收款金额', '结算备注', '开票状态', '收款状态'];

        $result = [];

        $rows = $orders->get()->toArray();

        foreach ($rows as $item) {
            $result[] = [
                $item['company_name'],
                $item['order']['insurance_company_name'],
                $item['order']['case_number'],
                $item['order']['province'] . $item['order']['city'] . $item['order']['area'] . $item['order']['address'],
                $item['order']['post_time'],
                $item['order']['license_plate'], // 标的车车牌
                $item['order']['insurance_check_name'], // 保险查勘人员
                $item['order']['insurance_check_phone'], // 电话
                '', // 审核人员
                $item['wusun_check_name'], // 物损查勘人员
                '', // 审核时间
                $item['order_number'], // 工单号
                $item['total_amount'], // 审核金额
                $item['total_amount'], // 结算金额
                $item['invoiced_amount'], // 已开票金额
                $item['paid_amount'], // 已收款金额
                '', // 结算备注
                ['', '未收款', '部分收款', '已收款'][(int)$item['invoice_status']], // 开票状态
                ['', '未开票', '部分开票', '已开票'][(int)$item['payment_status']], // 收款状态
            ];
        }

        (new ExportService)->excel($headers, $result, $fileName);
    }

    /**
     * 付款记录
     *
     * @param Request $request
     * @return JsonResponse|void
     */
    public function paymemtLog(Request $request)
    {
        $records = FinancialPaymentRecord::with('order:id,insurance_company_name')
            ->where('company_id', $request->user()->company_id)
            ->when($order_id = $request->input('order_id'), function ($query) use ($order_id) {
                $query->where('order_id', $order_id);
            })
            ->when($type = $request->input('financial_type'), function ($query) use ($type) {
                $query->where('financial_type', $type);
            })
            ->when($baoxiao = $request->input('baoxiao'), function ($query) use ($baoxiao) {
                $query->where('baoxiao', $baoxiao);
            })
            ->when($accountId = $request->input('bank_account_id'), function ($query) use ($accountId) {
                $query->where('bank_account_id', $accountId);
            })
            ->when($order_post_time_start = $request->input('order_post_time_start'), function ($query) use ($order_post_time_start) {
                $query->where('order_post_time', '>=', $order_post_time_start . ' 00:00:00');
            })
            ->when($order_post_time_end = $request->input('order_post_time_end'), function ($query) use ($order_post_time_end) {
                $query->where('order_post_time', '<=', $order_post_time_end . ' 23:59:59');
            })
            ->when($search = $request->input('search'), function ($query) use ($search) {
                $query->where(function ($query) use ($search) {
                    $query->where('order_number', 'like', "%$search%")
                        ->orWhere('case_number', 'like', "%$search%")
                        ->orWhere('license_plate', 'like', "%$search%");
                });
            })
            ->orderBy('id', 'desc');

        if (!$request->input('export')) {
            return success($records->paginate(getPerPage()));
        }

        $headers = ['所属公司', '外协单位', '客户', '客户经理', '付款时间', '付款金额', '付款备注', '对账内勤', '结算单号', '付款类型',
            '发票号', '发票类型', '开票金额', '开票单位', '开票时间', '收款账号', '收款凭证'];

        $rows = $records->get()->toArray();

        $result = [];
        foreach ($rows as $row) {
            $result[] = [
                $row['company_name'],
                '',
                $row['order']['insurance_company_name'],
                '',
                $row['payment_time'],
                $row['amount'],
                $row['remark'],
                '', // 对账内勤
                $row['order_number'], // 结算单号
                '', // 付款类型
                $row['invoice_number'], // 发票号
                ['', '专票', '普票'][(int)$row['invoice_type']], // 发票类型
                $row['invoice_amount'],
                $row['invoice_company_name'], // 开票单位
                $row['invoice_created_at'],
                $row['bank_name'] . "\n" . $row['bank_account_number'],
                'https://xinglan-1319638065.cos.ap-shanghai.myqcloud.com' . implode('https://xinglan-1319638065.cos.ap-shanghai.myqcloud.com', $row['payment_images'])
            ];
        }

        $fileName = $request->input('financial_type') == 1 ? '回款记录表' : '付款记录表';

        (new ExportService)->excel($headers, $result, $fileName);
    }
}
