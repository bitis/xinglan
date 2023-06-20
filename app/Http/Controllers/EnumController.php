<?php

namespace App\Http\Controllers;

use App\Models\Enumerations\CompanyType;
use App\Models\Enumerations\InsuranceType;
use App\Models\Enumerations\MenuType;
use App\Models\Enumerations\Status;
use App\Models\GoodsType;
use App\Models\Role;
use Illuminate\Http\JsonResponse;

class EnumController extends Controller
{
    public function goodsType(): JsonResponse
    {
        return success(GoodsType::where('status', Status::Normal)->get());
    }

    public function companyType(): JsonResponse
    {
        $companyType = [];

        foreach (CompanyType::cases() as $type) {
            $companyType[] = [
                'id' => $type,
                'name' => $type->name()
            ];
        }

        return success($companyType);
    }

    public function roleType(): JsonResponse
    {
        return success(Role::get());
    }

    public function insuranceType(): JsonResponse
    {
        $companyType = [];

        foreach (InsuranceType::cases() as $type) {
            $companyType[] = [
                'id' => $type,
                'name' => $type->name()
            ];
        }

        return success($companyType);
    }

    public function menuType(): JsonResponse
    {
        $menuType = [];

        foreach (MenuType::cases() as $type) {
            $menuType[] = [
                'id' => $type,
                'name' => $type->name()
            ];
        }

        return success($menuType);
    }
}
