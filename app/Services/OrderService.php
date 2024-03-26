<?php

namespace App\Services;

use App\Models\Company;
use App\Models\Enumerations\CompanyType;
use App\Models\Enumerations\InsuranceType;
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
     * @param array $with
     * @param array $groupId
     * @return Builder
     */
    public static function list(User $user, Collection $params, array $with = [], array $groupId = []): Builder
    {
        $current_company = $user->company;

        $company_id = $params->get('company_id');

        $role = str_replace($user->company_id . '_', '', $user->getRoleNames()->toArray()[0]);

        if ($user->can('ViewAllOrder')) $role = 'admin';

        $sysAdmin = $user->hasRole('admin');

        return Order::with($with)
            ->where(function ($query) use ($current_company, $company_id, $groupId, $sysAdmin) {
                if (!$sysAdmin && $current_company->car_part == 1)
                    $query->where('insurance_type', InsuranceType::CarPart->value);

                if ($company_id)
                    return match ($current_company->getRawOriginal('type')) {
                        CompanyType::BaoXian->value => $query->where('insurance_company_id', $company_id),
                        CompanyType::WuSun->value => $query->where('wusun_company_id', $company_id)
                            ->OrWhere('check_wusun_company_id', $company_id),
                        CompanyType::WeiXiu->value => $query->whereRaw("find_in_set($company_id, repair_company_ids)"),
                    };

                if (!$sysAdmin && empty($groupId)) $groupId = Company::getGroupId($current_company->id);

                if (!$sysAdmin) return match ($current_company->getRawOriginal('type')) {
                    CompanyType::BaoXian->value => $query->whereIn('insurance_company_id', $groupId),
                    CompanyType::WuSun->value => $query->whereIn('wusun_company_id', $groupId)
                        ->OrWhereIn('check_wusun_company_id', $groupId),
                    CompanyType::WeiXiu->value => $query->whereRaw("find_in_set('" . $current_company->id . "', repair_company_ids)"),
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
                    case '财务经理':
                    case '财务人员':
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
            ->when($params->get('start_at'), function ($query, $start_at) {
                $query->where('post_time', '>', $start_at);
            })
            ->when($params->get('end_at'), function ($query, $end_at) {
                $query->where('post_time', '<=', $end_at . ' 23:59:59');
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
            ->when(strlen($self_create = $params->get('self_create')), function ($query) use ($self_create) {
                if ($self_create) $query->where('creator_company_type', CompanyType::WuSun->value);
                else $query->where('creator_company_type', CompanyType::BaoXian->value);
            })
            ->when(strlen($insurance_company_id = $params->get('insurance_company_id')), function ($query) use ($insurance_company_id) {
                if ($insurance_company_id) $query->where('insurance_company_id', $insurance_company_id);
            })
            ->when($params->get('create_type'), function ($query, $create_type) use ($current_company) {
                if ($create_type == 1) // 自己创建
                    $query->where('creator_company_id', $current_company->id);
                elseif ($current_company->type == CompanyType::WuSun->value)
                    $query->where('creator_company_type', CompanyType::BaoXian->value);
            });
    }
}
