<?php

namespace App\Http\Controllers;

use App\Models\Company;
use App\Models\FinancialOrder;
use App\Models\FinancialPaymentRecord;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class FinancialController extends Controller
{
    /**
     * 财务列表
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function index(Request $request): JsonResponse
    {
        $company = $request->user()->company;
        $company_id = $request->input('company_id');

        $orders = FinancialOrder::when($request->get('type'), fn($query, $type) => $query->where('type', $type))
            ->where(function ($query) use ($company, $company_id) {
                if ($company_id) return $query->where('company_id', $company_id);

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
            ->when($request->get('insurance_company_id'), fn($query, $value) => $query->where('insurance_company_id', $value))
            ->when($request->get('opposite_company_id'), fn($query, $value) => $query->where('opposite_company_id', $value))
            ->when($request->get('wusun_check_id'), fn($query, $value) => $query->where('wusun_check_id', $value))
            ->when($request->get('payment_status'), fn($query, $value) => $query->where('payment_status', $value))
            ->when($request->get('invoice_status'), fn($query, $value) => $query->where('invoice_status', $value))
            ->when($request->get('post_time_start'), function ($query, $post_time_start) {
                $query->where('post_time', '>', $post_time_start);
            })
            ->when($request->get('post_time_end'), function ($query, $post_time_end) {
                $query->where('post_time', '<=', $post_time_end . ' 23:59:59');
            })
            ->where('check_status', 1)
            ->orderBy('id', 'desc')
            ->paginate(getPerPage());

        return success($orders);
    }

    /**
     * 付款记录
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function paymemtLog(Request $request)
    {
        $records = FinancialPaymentRecord::where('company_id', $request->user()->company_id)
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
            ->orderBy('id', 'desc')
            ->paginate(getPerPage());

        return success($records);
    }
}
