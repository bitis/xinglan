<?php

namespace App\Http\Controllers;

use App\Http\Requests\ProviderOptionRequest;
use App\Models\CompanyProvider;
use App\Models\ProviderOption;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;

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

        $options = ProviderOption::with('company:id,name')
            ->where('company_id', $current_company_id)
            ->when($request->input('province'), function ($query, $province) {
                $query->where('province', $province);
            })
            ->when($request->input('city'), function ($query, $city) {
                $query->where('city', $city);
            })
            ->when($request->input('provider_id'), function ($query, $provider_id) {
                $query->where('provider_id', $provider_id);
            })
            ->when($request->input('insurance_type'), function ($query, $insurance_type) {
                $query->where('insurance_type', $insurance_type);
            })
            ->paginate(getPerPage());
        return success($options);
    }

    /**
     * @param ProviderOptionRequest $request
     * @return JsonResponse
     */
    public function form(ProviderOptionRequest $request): JsonResponse
    {
        $params = $request->only(['company_id', 'provider_id', 'insurance_type', 'province', 'city', 'area', 'weight', 'status']);

        $option = ProviderOption::findOr($request->input('id'), function () {
            return new ProviderOption();
        });

        $option->fill(Arr::whereNotNull($params));

        $option->save();

        return success();
    }

    /**
     * 已配置的地区
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function getRepeatRegion(Request $request): JsonResponse
    {
        $params = Arr::whereNotNull($request->only(['company_id','provider_id','insurance_type','province','city']));

         $areas = ProviderOption::where($params)
             ->pluck('area');

         return success($areas->collapse());
    }
}
