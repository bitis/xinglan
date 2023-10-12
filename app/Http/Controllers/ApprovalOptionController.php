<?php

namespace App\Http\Controllers;

use App\Models\ApprovalOption;
use App\Models\Approver;
use App\Models\Company;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ApprovalOptionController extends Controller
{
    /**
     * 配置列表
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function index(Request $request): JsonResponse
    {
        $company_id = $request->input('company_id');
        $current_company_id = $request->user()->company_id;

        $options = ApprovalOption::with(['company:id,name', 'approver:id,name,mobile,avatar', 'extends:id,name,mobile,avatar'])
            ->where(function ($query) use ($current_company_id, $company_id) {
                if ($company_id) return $query->where('company_id', $company_id);
                return $query->whereIn('company_id', Company::getGroupId($current_company_id));
            })->when($request->input('type'), function ($query, $type) {
                $query->where('type', $type);
            })->paginate(getPerPage());

        return success($options);
    }

    /**
     * 新增、编辑
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function form(Request $request): JsonResponse
    {
        $option = ApprovalOption::where('type', $request->input('type'))
                ->where('company_id', $request->input('company_id'))->firstOr(fn() => new ApprovalOption());

        if (!in_array($request->input('company_id'), Company::getGroupId($request->user()->company_id)))
            return fail('非法操作');

        $option->fill($request->only(['company_id', 'type', 'approve_mode', 'review_mode', 'review_conditions']));

        $option->save();

        Approver::where('approval_option_id', $option->id)->delete();

        $merge = function (&$array, $append, $type) use ($option) {
            foreach ($append as $item) {
                $array[] = ['user_id' => $item, 'type' => $type, 'approval_option_id' => $option->id];
            }
        };

        $approvers = [];

        $merge($approvers, $request->input('approver', []), Approver::TYPE_CHECKER);
        $merge($approvers, $request->input('reviewer', []), Approver::TYPE_REVIEWER);
        $merge($approvers, $request->input('receiver', []), Approver::TYPE_RECEIVER);

        Approver::insert($approvers);

        $option->approvalExtends()->delete();

        if ($extends = $request->input('extends')) {
            $option->approvalExtends()->createMany($extends);
        }

        return success();
    }
}
