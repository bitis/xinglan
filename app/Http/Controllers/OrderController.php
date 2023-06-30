<?php

namespace App\Http\Controllers;

use App\Http\Requests\OrderRequest;
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
    protected int $company_id;
    protected User $user;

    public function __construct()
    {
        $this->user = \request()->user();
        $this->company_id = $this->user->company_id;
    }

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

    /**
     * 派遣物损查勘人员 （物损公司派遣本公司）
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function dispatchCheckUser(Request $request): JsonResponse
    {
        $params = $request->only(['wusun_check_id', 'wusun_check_name', 'wusun_check_phone']);

        $params['dispatch_check_at'] = now()->toDateTimeString();

        try {
            $this->dispatch($request->input('order_id'), $params, 'CheckUser');
        } catch (\Throwable $exception) {
            return fail($exception->getMessage());
        }

        return success();
    }

    /**
     * 派遣服务商（保险公司派遣无损公司）
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function dispatchProvider(Request $request): JsonResponse
    {
        $params = $request->only(['wusun_company_id', 'wusun_company_name', 'dispatch_wusun_at']);

        $params['dispatch_check_at'] = now()->toDateTimeString();

        try {
            $this->dispatch($request->input('order_id'), $params, 'Provider');
        } catch (\Exception $exception) {
            return fail($exception->getMessage());
        }

        return success();
    }

    /**
     * @throws \Throwable
     */
    public function dispatch($id, $params, $type)
    {
        $order = Order::find($id);

        if (!$order) return fail('工单未找到');

        throw_if($this->company_id != $order->wusun_company_id, '非本公司订单');

        $company = Company::find($this->company_id);

        if ($type == 'CheckUser') {
            throw_if($company->getRawOriginal('type') != CompanyType::WuSun->value, '只有物损公司可以派遣查勘');
        }

        if ($type == 'Provider') {
            throw_if($company->getRawOriginal('type') != CompanyType::BaoXian->value, '只有保险公司可以派遣服务商');
        }

        $order->fill($params);

        return $order->save();
    }

}
