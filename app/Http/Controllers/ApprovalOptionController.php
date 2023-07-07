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
        $current_company_id = $request->user()->id;

        $options = ApprovalOption::where(function ($query) use ($current_company_id, $company_id) {
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
        $option = ApprovalOption::findOr($request->input('id'), fn() => new ApprovalOption());

        if (!$option && ApprovalOption::where('type', $request->input('type'))
                ->where('company_id', $request->input('company_id'))->first())
            return fail('数据异常');

        if (!in_array($request->input('company_id'), Company::getGroupId($request->user()->company_id)))
            return fail('非法操作');

        $option->fill($request->only(['company_id', 'type', 'approve_type', 'review_type', 'review_conditions']));

        $option->save();

        Approver::where('approval_option_id', $option->id)->delete();

        $merge = function (&$array, $append, $type) use ($option) {
            foreach ($append as $item) {
                $array[] = ['user_id' => $item, 'type' => $type, 'approval_option_id' => $option->id];
            }
        };

        $approvers = [];

        $merge($approvers, $request->input('approver', []), Approver::TYPE_APPROVER);
        $merge($approvers, $request->input('reviewer', []), Approver::TYPE_REVIEWER);
        $merge($approvers, $request->input('receiver', []), Approver::TYPE_RECEIVER);

        Approver::insert($approvers);

        return success();
    }
}
