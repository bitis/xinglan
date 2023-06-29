<?php

namespace App\Http\Controllers;

use App\Http\Requests\OrderRequest;
use App\Models\Company;
use App\Models\CompanyProvider;
use App\Models\Enumerations\CompanyType;
use App\Models\Enumerations\Status;
use App\Models\Order;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class OrderController extends Controller
{

    /**
     * 客户选择
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function customer(Request $request): JsonResponse
    {
        $current_company = $request->user()->company;

        if ($current_company?->getRawOriginal('type') == CompanyType::BaoXian->value)
            return success([
                ['id' => $current_company->id, 'name' => $current_company->name]
            ]);

        $customers_id = CompanyProvider::where('provider_id', $current_company->id)
            ->where('status', Status::Normal)
            ->pluck('company_id');

        $customers = Company::whereIn('id', $customers_id)->select(['id', 'name'])->get();

        return success($customers);
    }

    /**
     * 工单列表
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function index(Request $request): JsonResponse
    {
        $user = $request->user();
        $current_company = $user->company;

        $company_id = $request->input('company_id');

        $roles = $user->getRoleNames()->toArray();

        $userList = Order::with('company:id,name')
            ->where(function ($query) use ($current_company, $company_id) {
                if ($company_id)
                    return match ($current_company->getRawOriginal('type')) {
                        CompanyType::BaoXian->value,
                        CompanyType::WuSun->value => $query->where('insurance_company_id', $company_id),
                        CompanyType::WeiXiu->value => $query->where('damage_company_id', $company_id),
                    };

                return match ($current_company->getRawOriginal('type')) {
                    CompanyType::BaoXian->value => $query->where('insurance_company_id', $current_company->id),
                    CompanyType::WuSun->value => $query->where('damage_company_id', $current_company->id),
                    CompanyType::WeiXiu->value => $query->where('repair_company_id', $current_company->id),
                };
            })->when($roles, function ($query, $roles) use ($user) {
                $rolesName = implode(',', $roles);
                if (strpos($rolesName, '查勘人员')) $query->where('creator_id', '=', $user->id);
            })->when($request->input('post_time_start'), function ($query, $post_time_start) {
                $query->where('post_time', '>', $post_time_start);
            })->when($request->input('post_time_end'), function ($query, $post_time_end) {
                $query->where('post_time', '<=', $post_time_end);
            })->when($request->input('insurance_type'), function ($query, $insurance_type) {
                $query->where('insurance_type', $insurance_type);
            })->when(strlen($order_status = $request->input('order_status')), function ($query) use ($order_status) {
                $query->where('order_status', $order_status);
            })->when(strlen($close_status = $request->input('close_status')), function ($query) use ($close_status) {
                $query->where('close_status', $close_status);
            })->when($request->input('name'), function ($query, $name) {
                $query->where(function ($query) use ($name) {
                    $query->where('order_number', 'like', "%$name%")
                        ->orWhere('case_number', 'like', "%$name%")
                        ->orWhere('license_plate', 'like', "%$name%")
                        ->orWhere('vin', 'like', "%$name%");
                });
            })->paginate(getPerPage());

        return success($userList);
    }

    /**
     * 新增、编辑
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function form(OrderRequest $request): JsonResponse
    {
        $orderParams = $request->only([
            'insurance_company_id',
            'order_number',
            'external_number',
            'case_number',
            'insurance_check_name',
            'insurance_check_phone',
            'post_time',
            'insurance_type',
            'license_plate',
            'vin',
            'locations',
            'province',
            'city',
            'area',
            'address',
            'creator_id',
            'creator_name',
            'insurance_people',
            'insurance_phone',
            'driver_name',
            'driver_phone',
            'remark',
            'customer_remark',
            'order_status',
            'close_status',
            'goods_types',
            'goods_name',
            'owner_name',
            'owner_phone',
            'owner_price',
            'images',
            'goods_remark',
        ]);

        $user = $request->user();

        $order = Order::findOr($request->input('id'), fn() => new Order(['creator_id' => $user->id, 'creator_name' => $user->name]));

        $order->fill($orderParams);

        $order->save();

        return success();
    }

}
