<?php

namespace App\Http\Controllers;

use App\Models\BankAccount;
use App\Models\Company;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class BankAccountController extends Controller
{
    /**
     * 银行账户
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function index(Request $request): JsonResponse
    {
        $current_company = $request->user()->company;

        $company_id = $request->input('company_id');

        $bank_accounts = BankAccount::with('company:id,name')
            ->where(function ($query) use ($current_company, $company_id) {
                if ($company_id) return $query->where('company_id', $company_id);

                return $query->OrWhereIn('company_id', Company::getGroupId($current_company->id));
            })->paginate(getPerPage());

        return success($bank_accounts);
    }

    /**
     * 编辑
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function form(Request $request): JsonResponse
    {
        $account = BankAccount::findOr($request->input('id'), fn() => new BankAccount);

        $account->fill($request->only(['company_id', 'bank_name', 'number', 'no', 'remark']));

        $account->save();

        return success($account);
    }
}
