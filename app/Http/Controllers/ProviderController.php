<?php

namespace App\Http\Controllers;

use App\Http\Requests\ProviderRequest;
use App\Jobs\CreateCompany;
use App\Models\Company;
use App\Models\CompanyProvider;
use App\Models\Enumerations\CompanyLevel;
use App\Models\Enumerations\Status;
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
        $providers = CompanyProvider::with('company:id,name')
            ->where('company_id', $request->user()->company_id)
            ->when($request->input('name'), function ($query, $name) {
                $query->where('name', 'like', "%$name%");
            })->when($request->input('status'), function ($query, $status) {
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
            'introduce',
            'remark',
            'status',
        ]);

        $currentCompany = $request->user()->company;

        try {
            DB::beginTransaction();
            $provider = CompanyProvider::where('company_id', $request->user()->company_id)
                ->findOr($request->input('id'), function () use ($companyParams, $adminParams, $currentCompany) {
                    $providerCompany = Company::findOr($companyParams['provider_id'], function () use ($companyParams, $adminParams) {
                        $company = new Company([
                            'level' => CompanyLevel::One,
                            'parent_id' => 0,
                            'status' => Status::Normal,
                            'invite_code' => rand(100000, 999999),
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
                        CreateCompany::dispatch($company)->afterResponse();
                        return $company;
                    });

                    if (CompanyProvider::where([
                        'company_id' => $currentCompany->id,
                        'provider_id' => $providerCompany->id,
                    ])->exists()) throw new \Exception('该公司已被添加');

                    return new CompanyProvider([
                        'company_id' => $currentCompany->id,
                        'provider_id' => $providerCompany->id,
                        'provider_name' => $providerCompany->name,
                    ]);
                });

            $provider->fill($providerParams);
            $provider->save();
            DB::commit();
        } catch (\Exception $exception) {
            DB::rollBack();
            if (app()->environment('local')) throw $exception;
            return fail($exception->getMessage());
        }
        return success();
    }
}