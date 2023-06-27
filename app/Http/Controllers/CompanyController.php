<?php

namespace App\Http\Controllers;

use App\Http\Requests\CompanyRequest;
use App\Jobs\CreateCompany;
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
        $user = $request->user();

        $name = $request->input('name');
        $status = $request->input('status');
        $parent_id = $request->input('parent_id');

        $companies = Company::with('parent')
            ->when(!$user->hasRole('admin'), function ($query) use ($user) {
                $query->whereIn('id', Company::getGroupId($user->company_id));
            })
            ->when($name, function ($query, $name) {
                $query->where('name', 'like', "%$name%");
            })
            ->when(strlen($status), function ($query) use ($status) {
                $query->where('status', $status);
            })
            ->when($parent_id, function ($query, $parent_id) {
                $query->where('parent_id', $parent_id);
            })
            ->paginate(getPerPage());

        return success($companies);
    }

    public function tree(Request $request): JsonResponse
    {
        $company = $request->user()->company;

        $top_id = $company->top_id ?: $company->id;

        $company = Company::with(['children:id,name,parent_id', 'children.children:id,name,parent_id'])
            ->where('top_id', $top_id)
            ->where('parent_id', 0)
            ->select(['id', 'name', 'parent_id'])
            ->get();

        return success($company);
    }

    public function form(CompanyRequest $request): JsonResponse
    {
        try {
            DB::beginTransaction();

            $company = Company::findOr($request->input('id'), function () use ($request) {
                $parent_id = $request->input('parent_id', 0);

                $parent = Company::find($parent_id);

                if ($parent && $parent->level == CompanyLevel::Three->value) throw new \Exception('三级公司不允许创建下级公司');

                $level = $parent ? min($parent->levle + 1, CompanyLevel::Three->value) : CompanyLevel::One->value;

                return new Company([
                    'level' => $level,
                    'parent_id' => $parent_id,
                    'status' => Status::Normal,
                    'invite_code' => rand(100000, 999999),
                    'top_id' => ($level == CompanyLevel::One->value) ? 0 : $parent->top_id,
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

                if ($company->level == CompanyLevel::One->value) {
                    $company->top_id = $company->id;
                }

                $user = $company->users()->create([
                    'account' => $request->input('account'),
                    'name' => $request->input('contract_name'),
                    'mobile' => $request->input('contract_phone'),
                    'status' => Status::Normal,
                    'password' => bcrypt(config('default.password')),
                ]);

                $company->admin_id = $user->id;

                $company->save();

                CreateCompany::dispatch($company)->afterResponse();
            }

            DB::commit();
        } catch (\Exception $exception) {
            DB::rollBack();
            if (app()->environment('local')) throw $exception;
            return fail($exception->getMessage());
        }

        return success($company);
    }

    /**
     * 当前公司分支机构
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function branch(Request $request): JsonResponse
    {
        $company_id = $request->user()->company_id;

        $top = Company::find($company_id);

        $second = Company::where('parent_id', $company_id)->get()->toArray();

        $three = Company::whereIn('parent_id', array_column($second, 'id'))->get()->toArray();

        return success(array_merge([$top], $second, $three));
    }
}
