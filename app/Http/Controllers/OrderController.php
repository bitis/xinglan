<?php

namespace App\Http\Controllers;

use App\Http\Requests\UserRequest;
use App\Models\Company;
use App\Models\CompanyProvider;
use App\Models\Enumerations\CompanyType;
use App\Models\Enumerations\Status;
use App\Models\Order;
use App\Models\User;
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
        $company_id = $request->input('company_id');
        $role = $request->input('role');
        $status = $request->input('status');
        $text = $request->input('text');

        $userList = User::with('roles')
            ->when($company_id, function ($query, $company_id) {
                $query->where('company_id', $company_id);
            })->when($role, function ($query, $role) {
                $query->role($role);
            })->when($status, function ($query, $status) {
                $query->where('status', $status);
            })->when($text, function ($query, $text) {
                $query->where('name', 'like', "%$text%")
                    ->where('account', 'like', "%$text%")
                    ->where('mobile', 'like', "%$text%");
            })->paginate(getPerPage());

        return success($userList);
    }

    /**
     * 新增、编辑
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function form(Request $request): JsonResponse
    {
        $orderParams = $request->only([
            'company_id',
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

        $order = Order::findOr($request->input('id'), fn() => new User(['company_id' => $request->user()->company_id]));

        $order->fill($orderParams);

        $order->save();

        return success($order);
    }

}
