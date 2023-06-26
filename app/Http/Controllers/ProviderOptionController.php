<?php

namespace App\Http\Controllers;

use App\Models\CompanyProvider;
use App\Models\ProviderOption;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ProviderOptionController extends Controller
{
    /**
     * 外协单位配置列表
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function index(Request $request): JsonResponse
    {
        $current_company_id = $request->user()->company_id;

        $providers_id = CompanyProvider::where('company_id', )->pluck('provider_id');

        $options = ProviderOption::with('company:id,name')
            ->whereIn('provider_id', $providers_id)
            ->where('company_id', $current_company_id)
            ->when($request->input('province'), function ($query, $province) {
                $query->where('province', $province);
            })
            ->when($request->input('city'), function ($query, $city) {
                $query->where('city', $city);
            })
            ->paginate(getPerPage());
        return success($options);
    }

    public function form(Request $request)
    {

    }
}
