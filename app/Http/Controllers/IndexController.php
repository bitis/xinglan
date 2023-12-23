<?php

namespace App\Http\Controllers;

use App\Models\ApprovalOrderProcess;
use App\Models\Company;
use App\Models\Enumerations\ApprovalStatus;
use App\Models\Enumerations\CompanyType;
use App\Models\Enumerations\OrderStatus;
use App\Services\OrderService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;

class IndexController extends Controller
{
    /**
     * APP 首页宫格数据
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function index(Request $request): JsonResponse
    {
        $params = $request->collect();

        $user = $request->user();

        $company = $user->company;

        $result = [];

        $order_status = OrderStatus::cases();

        if ($company->getRawOriginal('type') == CompanyType::WeiXiu->value) {
            $order_status = [
                OrderStatus::WaitRepair,
                OrderStatus::Repairing,
                OrderStatus::Repaired,
                OrderStatus::Paid,
            ];
        }

        $groupId = Company::getGroupId($company->id);

        foreach ($order_status as $item) {
            $collect = $params->merge(['order_status' => $item->value]);
            $result[$item->name] = OrderService::list($request->user(), $collect, [], $groupId)->without('lossPersons')->count();
        }

        $result['all'] = OrderService::list($request->user(), $params)->without('lossPersons')->count();
        return success($result);
    }

    /**
     * 获取各类型的计数
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function count(Request $request): JsonResponse
    {
        $result = [];

        $types = explode(',', $request->input('types'));

        if (in_array('approval', $types)) {
            $result['approval'] = ApprovalOrderProcess::where('user_id', $request->user()->id)
                ->where('approval_status', ApprovalStatus::Pending->value)
                ->whereIn('step', [1, 2])
                ->where('hidden', false)
                ->count();
        }

        return success($result);
    }
}
