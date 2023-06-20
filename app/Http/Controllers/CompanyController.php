<?php

namespace App\Http\Controllers;

use App\Http\Requests\CompanyRequest;
use App\Models\Company;
use App\Models\Enumerations\CompanyLevel;
use App\Models\Enumerations\Status;
use App\Models\Role;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class CompanyController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $name = $request->input('name');
        $status = $request->input('status');

        $companies = Company::with('parent')
            ->when($name, function ($query, $name) {
                $query->where('name', 'like', "%$name%");
            })
            ->when($status, function ($query, $status) {
                $query->where('status', $status);
            })->paginate($request->input('limit', 15));

        return success($companies);
    }

    public function form(CompanyRequest $request): JsonResponse
    {
        try {
            DB::beginTransaction();

            $company = Company::findOr($request->input('id'), function () use ($request) {
                $parent_id = $request->input('parent_id', 0);

                $level = $parent_id ? min(Company::find($parent_id)->levle + 1, CompanyLevel::Three) : CompanyLevel::One;

                return new Company([
                    'level' => $level,
                    'parent_id' => $parent_id,
                    'status' => Status::Normal,
                    'invite_code' => rand(100000, 999999)
                ]);
            });

            $company->fill($request->only([
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
            ]));

            $company->save();

            if (!$request->input('id')) {

                $defaultRoles = ['公司管理员', '施工经理', '施工人员', '查勘经理', '查勘人员', '财务经理', '财务人员', '调度内勤', '出纳人员', '造价员',];

                foreach ($defaultRoles as $defaultRole) {
                    $role = $company->roles()->create([
                        'name' => $company->id . '_' . $defaultRole,
                        'guard_name' => 'api',
                        'show_name' => $defaultRole
                    ]);

                    $role->givePermissionTo(Role::where('name', $defaultRole)->first()?->permissions?->pluck('name'));
                }

                $user = $company->users()->create([
                    'account' => $request->input('account'),
                    'name' => $request->input('contract_name'),
                    'mobile' => $request->input('contract_phone'),
                    'status' => Status::Normal,
                    'password' => bcrypt(config('default.password')),
                ]);

                $user->assignRole($company->id . '_公司管理员');

                $company->admin_id = $user->id;

                $company->save();
            }
        } catch (\Exception $exception) {
            DB::rollBack();
            if (app()->environment('test')) throw $exception;
            return fail($exception->getMessage());
        }

        return success($company);
    }
}
