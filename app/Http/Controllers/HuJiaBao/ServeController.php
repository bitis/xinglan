<?php

namespace App\Http\Controllers\HuJiaBao;

use App\Common\HuJiaBao\ApiClient;
use App\Common\HuJiaBao\Response;
use App\Http\Controllers\Controller;
use App\Models\HuJiaBao\TaskInfo;
use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ServeController extends Controller
{
    /**
     * 接收查勘任务推送
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function receiveInvestigationTask(Request $request): JsonResponse
    {
        $body = $request->input('data');

        return Response::success();
    }

    /**
     * 上传文件
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function upload(Request $request, ApiClient $client): JsonResponse
    {
        try {
            $response = $client->upload($request->file('Files'), '12', '12', '122');
        } catch (\Exception $e) {
            return fail($e->getMessage());
        }

        return success($response);
    }

    /**
     * 查勘任务列表
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function tasks(Request $request): JsonResponse
    {

        return success();
    }

    /**
     * 提交查勘资料
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function investigation(Request $request, ApiClient $client): JsonResponse
    {
        // 根据任务信息获取子赔单

        $taskInfo = TaskInfo::where('id', $request->input('TaskID'))->first();

        if (!$taskInfo) return fail('任务不存在');

        $subClaimInfo = $taskInfo->subClaimInfo;

        $claimInfo = $subClaimInfo->claimInfo;

        $investigationInfo = $request->only([
            'PropertyNature',
            'IsInvolveRecovery',
            'InvestigatorContact',
            'InvestigatorArrivalDate',
            'InvestigationProvince',
            'InvestigationCity',
            'InvestigationDistrict',
            'InvestigationDetailAddress',
            'InvestigationDescription',
            'PropertyTotalEstimatedAmount',
            'Remark',
        ]);

        $LossItemList = $request->input('LossItemList');

        try {
            DB::beginTransaction();

            $taskInfo->investigationInfo()->updateOrCreate(['task_info_id' => $taskInfo->id], $investigationInfo);

            $taskInfo->investigationInfo->lossItemList()->delete();
            $taskInfo->investigationInfo->lossItemList()->createMany($LossItemList);

            $investigationInfo['LossItemList'] = $LossItemList;

            $form = collect($subClaimInfo->toArray())->except([
                'id',
                'claim_info_id',
                'created_at',
                'updated_at',
                'claim_info',
            ])->merge([
                'ClaimNo' => $claimInfo->ClaimNo,
                'TaskID' => $taskInfo->TaskID,
                'InvestigationInfo' => $investigationInfo,
            ]);

            $client->investigation($form);

            DB::commit();
        } catch (Exception $e) {
            DB::rollBack();
            return fail($e->getMessage());
        }

        return success();
    }

    /**
     * 接收定损理算任务
     *
     * @return JsonResponse
     */
    public function receiveAppraisalTask(): JsonResponse
    {

        return Response::success();
    }

    /**
     * 定损理算
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function appraisal(Request $request): JsonResponse
    {
        return success();
    }


    /**
     * 接收任务关闭通知
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function receiveTaskCanceled(Request $request): JsonResponse
    {
        $request->only([
            'ClaimNo',  // 理赔编号
            'TaskID',  // 任务ID 核心任务唯一ID
            'SubClaim',  // 子赔案
            'TaskType',  // 任务类型 《公用代码》-任务类型
        ]);

        return Response::success();
    }

    /**
     * 接收核赔通过通知
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function receiveAppraisalPass(Request $request): JsonResponse
    {
        $request->only([
            'ClaimNo',  // 理赔编号
            'SubClaim',  // 子赔案
            'CalculationTimes',  // 理算次数
            'IsDeclined',  // 是否拒赔 《公用代码》-是否代码
        ]);

        return Response::success();
    }
}
