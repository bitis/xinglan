<?php

namespace App\Http\Controllers;

use App\Models\Enumerations\ApprovalType;
use App\Models\Enumerations\CompanyType;
use App\Models\Enumerations\InsuranceType;
use App\Models\Enumerations\MenuType;
use App\Models\Enumerations\MessageType;
use App\Models\Enumerations\OrderCloseStatus;
use App\Models\Enumerations\OrderPlanType;
use App\Models\Enumerations\OrderStatus;
use App\Models\Enumerations\Status;
use App\Models\GoodsType;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class EnumController extends Controller
{
    public function goodsType(Request $request): JsonResponse
    {
        return success(GoodsType::where('type', $request->input('type', 0))
            ->whereIn('company_id', [$request->user()->company_id, 0])
            ->where('status', Status::Normal)->get());
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

    public function orderCloseStatus(): JsonResponse
    {
        return success(OrderCloseStatus::toArray());
    }

    public function messageType(): JsonResponse
    {
        return success(MessageType::toArray());
    }

    public function orderPlanType(): JsonResponse
    {
        return success(OrderPlanType::toArray());
    }

    public function approvalType(Request $request): JsonResponse
    {
        if ($request->user()->company?->getRawOriginal('type') == CompanyType::BaoXian->value) {
            return success([
                [
                    'id' => ApprovalType::ApprovalQuotation,
                    'name' => ApprovalType::ApprovalQuotation->name(),
                ], [
                    'id' => ApprovalType::ApprovalRepaired,
                    'name' => ApprovalType::ApprovalRepaired->name(),
                ],
            ]);
        }

        return success(ApprovalType::toArray());
    }
}
