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

        if (empty($customer) && $request->input('company_id')) {
            $customer = new CompanyProvider([
                'provider_id' => $request->user()->company_id,
                'provider_name' => $request->user()->company->name,
                'provider_company_type' => $request->user()->company->getRawOriginal('type'),
                'status' => 0
            ]);
            $customer->fill($request->only([
                'company_id',
                'expiration_date',
                'car_insurance',
                'other_insurance',
                'car_part'
            ]));
        }

        $groups = Company::getGroupId($request->user()->company_id);

        if (!in_array($customer->provider_id, $groups)) return fail('没有修改权限');

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

        if ($request->input('auto_link_sub_company')) {
            foreach ($groups as $sub_company_id) {
                if (!CompanyProvider::where('provider_id', $sub_company_id)->where('company_id', $customer->company_id)->exists()) {
                    $company = Company::find($sub_company_id);
                    CompanyProvider::create(array_merge([
                        'provider_id' => $sub_company_id,
                        'company_id' => $customer->company_id,
                        'provider_name' => $company->name,
                        'provider_company_type' => $company->getRawOriginal('type'),
                        'status' => 0,
                        'expiration_date' => $request->input('expiration_date'),
                        'car_insurance' => $request->input('car_insurance'),
                        'other_insurance' => $request->input('other_insurance'),
                        'car_part' => $request->input('car_part'),
                    ]), $request->only([
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
                }
            }
        }

        return success();
    }
}
