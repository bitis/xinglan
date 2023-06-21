<?php

namespace App\Http\Controllers;

use App\Http\Requests\ProviderRequest;
use App\Models\Company;
use App\Models\CompanyProvider;
use App\Models\Enumerations\CompanyLevel;
use App\Models\Enumerations\Status;
use App\Models\Role;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ProviderController extends Controller
{
    /**
     * 获取外协公司列表
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function index(Request $request): JsonResponse
    {
        $providers = $request->user()->company->providers()
            ->when($request->input('name'), function ($query, $name) {
                $query->where('name', 'like', "%$name%");
            })->when($request->input('status'), function ($query, $status) {
                $query->where('status', $status);
            })
            ->orderBy('id', 'desc')
            ->paginate(getPerPage());

        return success($providers);
    }

    public function form(ProviderRequest $request)
    {
        $companyParams = $request->only([
            'invite_code',
            'type',
            'name',
            'contract_name',
            'contract_phone',
            'province',
            'city',
            'area',
            'address',
            'status',
            'bank_name',
            'bank_account_name',
            'bank_account_number',
            'official_seal',
            'logo',
            'remark',
            'service_rate'
        ]);

        CompanyProvider::where('company_id', $request->user()->compony_id)->findOr($request->input('id'), function () use ($companyParams) {
            $company = new Company([
                'level' => CompanyLevel::One,
                'parent_id' => 0,
                'status' => Status::Normal,
                'invite_code' => rand(100000, 999999),
            ]);

            $company->fill($companyParams);
            $company->save();
            $company->top_id = $company->id;
            $company->save();
        });
    }
}
