<?php

namespace App\Http\Controllers;

use App\Models\Company;
use App\Models\CompanyProvider;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CustomerController extends Controller
{
    /**
     * 获取指定公司的客户列表。
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function index(Request $request): JsonResponse
    {
        $current_company_id = $request->user()->company_id;
        $company_id = $request->input('company_id');

        $customers = CompanyProvider::with([
            'company:id,name',
            'provider:id,name'
        ])
            ->join('companies', 'company_providers.company_id', '=', 'companies.id')
            ->where(function ($query) use ($current_company_id, $company_id) {
                if ($company_id) return $query->where('provider_id', $company_id);

                return $query->whereIn('provider_id', Company::getGroupId($current_company_id));
            })
            ->when($request->input('name'), function ($query, $name) {
                $query->where('companies.name', 'like', "%$name%");
            })->when(strlen($status = $request->input('status')), function ($query) use ($status) {
                $query->where('companies.status', $status);
            })
            ->selectRaw("company_providers.*")
            ->orderBy('company_providers.id', 'desc')
            ->paginate(getPerPage());

        return success($customers);
    }

    /**
     * 修改客户信息
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function form(Request $request): JsonResponse
    {
        $id = $request->input('id');

        $customer = CompanyProvider::find($id);

        if (empty($customer)) return fail('客户不存在');

        if (!in_array($customer->provider_id, Company::getGroupId($request->user()->company_id))) return fail('没有修改权限');

        $customer->fill($request->only([
            'company_name',
            'status',
            'customer_remark',
            'customer_tax_name',
            'customer_number',
            'customer_remark',
            'customer_address',
            'customer_license_no',
            'customer_phone',
            'customer_bank_name',
            'customer_bank_account_number',
            'customer_mailing_address',
            'contract_files',
            'contract_expiration_date',
            'customer_tex_remark',
        ]));

        $customer->save();

        return success();
    }
}
