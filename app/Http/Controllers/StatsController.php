<?php

namespace App\Http\Controllers;

use App\Models\Company;
use App\Models\Enumerations\CompanyType;
use App\Models\Order;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class StatsController extends Controller
{
    /**
     * 统计
     * @param Request $request
     * @return JsonResponse
     */
    public function areaCase(Request $request): JsonResponse
    {
        $user = $request->user();

        $company = $user->company;

        Order::when(
            $request->input('company_id'),
            function ($query, $company_id) use ($company) {
                return match ($company->getRawOriginal('type')) {
                    CompanyType::BaoXian->value => $query->where('insurance_company_id', $company_id),
                    CompanyType::WuSun->value => $query->where('wusun_company_id', $company_id),
                };
            },
            function ($query) use ($company) {
                $group = Company::getGroupId($company->id);
                return match ($company->getRawOriginal('type')) {
                    CompanyType::BaoXian->value => $query->whereIn('insurance_company_id', $group),
                    CompanyType::WuSun->value => $query->whereIn('wusun_company_id', $group),
                };
            });

        return success();
    }
}
