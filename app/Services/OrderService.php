<?php

namespace App\Services;

use App\Models\Company;
use App\Models\Enumerations\CompanyType;
use App\Models\Enumerations\OrderStatus;
use App\Models\Order;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

class OrderService
{
    /**
     * @param User $user
     * @param Collection $params
     * @return Builder
     */
    public static function list(User $user, Collection $params, $with = []): Builder
    {
        $current_company = $user->company;

        $company_id = $params->get('company_id');

        $role = str_replace($user->company_id . '_', '', $user->getRoleNames()->toArray()[0]);

        return Order::with($with)
            ->where(function ($query) use ($current_company, $company_id) {
                if ($company_id)
                    return match ($current_company->getRawOriginal('type')) {
                        CompanyType::BaoXian->value => $query->where('insurance_company_id', $company_id),
                        CompanyType::WuSun->value => $query->where('wusun_company_id', $company_id)
                            ->OrWhere('check_wusun_company_id', $company_id),
                    };

                $groupId = Company::getGroupId($current_company->id);

                return match ($current_company->getRawOriginal('type')) {
                    CompanyType::BaoXian->value => $query->whereIn('insurance_company_id', $groupId),
                    CompanyType::WuSun->value => $query->whereIn('wusun_company_id', $groupId)
                        ->OrWhereIn('check_wusun_company_id', $groupId),
                };
            })
            ->when($params->get('customer_id'), function ($query, $customer_id) use ($current_company) {
                return match ($current_company->getRawOriginal('type')) {
                    CompanyType::BaoXian->value,
                    CompanyType::WuSun->value => $query->where('insurance_company_id', $customer_id),
                };
            })
            ->when($params->get('wusun_check_id'), function ($query, $wusun_check_id) {
                return $query->where('wusun_check_id', $wusun_check_id);
            })
            ->when($role, function ($query, $role) use ($user) {
                switch ($role) {
                    case '查勘人员':
                        $query->where(function ($query) use ($user) {
                            $query->where('orders.creator_id', '=', $user->id)
                                ->orWhere('wusun_check_id', '=', $user->id)
                                ->orWhere('wusun_repair_user_id', '=', $user->id);
                        });
                        break;
                    case '施工经理':
                    case '施工人员':
                        $query->where(function ($query) use ($user) {
                            $query->where('orders.creator_id', '=', $user->id)
                                ->orWhere('wusun_check_id', '=', $user->id)
                                ->orWhere('wusun_repair_user_id', '=', $user->id);
                        });
                        break;
                    case '查勘经理':
                    case 'admin':
                    case '公司管理员':
                    case '造价员':
                        break;
                }
            })
            ->when($params->get('post_time_start'), function ($query, $post_time_start) {
                $query->where('post_time', '>', $post_time_start);
            })
            ->when($params->get('post_time_end'), function ($query, $post_time_end) {
                $query->where('post_time', '<=', $post_time_end . ' 23:59:59');
            })
            ->when($params->get('insurance_type'), function ($query, $insurance_type) {
                $query->where('insurance_type', $insurance_type);
            })
            ->when(strlen($order_status = $params->get('order_status')), function (Builder $query) use ($order_status) {
                return OrderStatus::from($order_status)->filter($query);
            })
            ->when(strlen($close_status = $params->get('close_status')), function ($query) use ($close_status) {
                $query->where('close_status', $close_status);
            })
            ->when($params->get('name'), function ($query, $name) {
                $query->where(function ($query) use ($name) {
                    $query->where('order_number', 'like', "%$name%")
                        ->orWhere('case_number', 'like', "%$name%")
                        ->orWhere('license_plate', 'like', "%$name%")
                        ->orWhere('vin', 'like', "%$name%");
                });
            })
            ->when($params->get('create_type'), function ($query, $create_type) use ($current_company) {
                if ($create_type == 1) // 自己创建
                    $query->where('creator_company_id', $current_company->id);
                elseif ($current_company->type == CompanyType::WuSun->value)
                    $query->where('creator_company_type', CompanyType::BaoXian->value);
            });
    }
}
