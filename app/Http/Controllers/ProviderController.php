<?php

namespace App\Http\Controllers;

use App\Http\Requests\ProviderRequest;
use App\Jobs\CreateCompany;
use App\Models\Company;
use App\Models\CompanyProvider;
use App\Models\Enumerations\CompanyLevel;
use App\Models\Enumerations\CompanyType;
use App\Models\Enumerations\Status;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

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
        $providers = CompanyProvider::with([
            'company:id,name',
            'provider:id,name,province,city,area,address,contract_name,contract_phone'
        ])
            ->where('company_id', $request->user()->company_id)
            ->when($request->input('name'), function ($query, $name) {
                $query->where('provider_name', 'like', "%$name%");
            })
            ->when(strlen($status = $request->input('status')), function ($query) use ($status) {
                $query->where('status', $status);
            })
            ->orderBy('id', 'desc')
            ->paginate(getPerPage());

        return success($providers);
    }

    public function form(ProviderRequest $request): JsonResponse
    {
        $request->whenHas('name', function (string $input) use ($request) {
            $request->merge(['provider_name' => $input]);
        });

        $request->whenMissing('provider_id', function () use ($request) {
            $request->merge(['provider_id' => 0]);
        });

        $adminParams = $request->only([
            'account',
            'contract_name',
            'contract_phone'
        ]);
        $companyParams = $request->only([
            'provider_id',
            'name',
            'contract_name',
            'contract_phone',
            'backup_contract_phone',
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
            'service_rate',
            'car_part'
        ]);
        $providerParams = $request->only([
            'provider_name',
            'bank_name',
            'bank_account_name',
            'bank_account_number',
            'license_no',
            'license_image',
            'expiration_date',
            'car_insurance',
            'other_insurance',
            'car_part',
            'introduce',
            'remark',
            'status',
        ]);

        $currentCompany = $request->user()->company;

        try {
            DB::beginTransaction();
            $provider = CompanyProvider::where('company_id', $request->user()->company_id)
                ->findOr($request->input('id'), function () use ($companyParams, $adminParams, $currentCompany, $providerParams) {
                    $providerCompany = Company::findOr($companyParams['provider_id'], function () use ($companyParams, $adminParams, $currentCompany, $providerParams) {
                        $providerType = $currentCompany->getRawOriginal('type') + 1;

                        if (!CompanyType::from($providerType)) throw new \Exception('维修公司不允许添加外协');

                        if (User::where('account', $adminParams['account'])->exists()) {
                            throw new \Exception('当前输入账号已存在');
                        }

                        $company = new Company([
                            'level' => CompanyLevel::One,
                            'parent_id' => 0,
                            'status' => Status::Normal,
                            'invite_code' => rand(100000, 999999),
                            'type' => $providerType,
                            'car_part' => $providerParams['car_part'] ?? false,
                        ]);

                        $company->fill($companyParams);
                        $company->save();

                        $admin = $company->users()->create(array_merge([
                            'name' => $adminParams['contract_name'],
                            'account' => $adminParams['account'],
                            'mobile' => $adminParams['contract_phone'],
                            'status' => Status::Normal,
                            'password' => bcrypt(config('default.password')),
                        ]));

                        $company->admin_id = $admin->id;
                        $company->top_id = $company->id;
                        $company->save();

                        CreateCompany::dispatch($company)->afterCommit();

                        return $company;
                    });

                    if (CompanyProvider::where([
                        'company_id' => $currentCompany->id,
                        'provider_id' => $providerCompany->id,
                    ])->exists()) throw new \Exception('该公司已被添加');

                    $providerCompany->car_part = $providerParams['car_part'] ?? false;
                    $providerCompany->save();

                    return new CompanyProvider([
                        'company_id' => $currentCompany->id,
                        'company_name' => $currentCompany->name,
                        'provider_id' => $providerCompany->id,
                        'provider_name' => $providerCompany->name,
                        'provider_company_type' => $providerCompany->getRawOriginal('type'),
                    ]);
                });

            $provider->fill($providerParams);

            if (!$provider->id) $provider->company_name = $currentCompany->name;
            $provider->save();
            DB::commit();
        } catch (\Exception $exception) {
            DB::rollBack();
            if (app()->environment('local')) throw $exception;
            return fail($exception->getMessage());
        }
        return success();
    }

    public function items(Request $request): JsonResponse
    {
        $providers = CompanyProvider::where('company_id', $request->user()->company_id)
            ->select(['id', 'provider_id', 'provider_name'])
            ->get();

        return success($providers);
    }
}
