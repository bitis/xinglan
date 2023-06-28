<?php

namespace App\Http\Controllers;

use App\Models\Enumerations\CompanyType;
use App\Models\Enumerations\InsuranceType;
use App\Models\Enumerations\MenuType;
use App\Models\Enumerations\OrderStatus;
use App\Models\Enumerations\Status;
use App\Models\GoodsType;
use Illuminate\Http\JsonResponse;

class EnumController extends Controller
{
    public function goodsType(): JsonResponse
    {
        return success(GoodsType::where('status', Status::Normal)->get());
    }

    public function companyType(): JsonResponse
    {
        return success(CompanyType::toArray());
    }

    public function insuranceType(): JsonResponse
    {
        return success(InsuranceType::toArray());
    }

    public function menuType(): JsonResponse
    {
        return success(MenuType::toArray());
    }

    public function orderStatus(): JsonResponse
    {
        return success(OrderStatus::toArray());
    }
}
