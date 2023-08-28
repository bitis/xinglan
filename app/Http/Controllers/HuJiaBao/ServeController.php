<?php

namespace App\Http\Controllers\HuJiaBao;

use App\Common\HuJiaBao\ApiClient;
use App\Common\HuJiaBao\Response;
use App\Http\Controllers\Controller;
use App\Models\AppraisalTask;
use App\Models\HuJiaBao\ClaimInfo;
use App\Models\HuJiaBao\Log;
use App\Models\HuJiaBao\PolicyInfo;
use App\Models\HuJiaBao\TaskInfo;
use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
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
        Log::create([
            'type' => '查勘任务推送',
            'url' => request()->fullUrl(),
            'request' => json_encode(request()->all()),
        ]);

        $PolicyInfoParams = $request->collect('PolicyInfo');

        $policyInfo = PolicyInfo::create($PolicyInfoParams->only([
            "PolicyNo",
            "ProductType",
            "EffectiveDate",
            "ExpireDate",
            "PolicyStatus",
            "StandardPremium",
        ])->toArray());

        $PropertyParams = collect($PolicyInfoParams->get('Property'));

        $propertyInfo = $policyInfo->property()->create($PropertyParams->only([
            "PropertyProvince",
            "PropertyCity",
            "PropertyDistrict",
            "PropertyDetailAddress",
        ])->toArray());

        foreach ($PropertyParams->get('CoverageList') as $item) {
            $coverageInfo = $propertyInfo->coverageList()->create(Arr::only($item, [
                "IsFinalLevelCt",
                "CoverageCode",
                "SumInsured",
                "SumPaymentAmt",
            ]));

            $coverageInfo->benefitList()->createMany($item['BenefitList']);
        }

        $ClaimInfoParams = collect($request->collect('ClaimInfo'));

        if ($ClaimInfoParams) {
            $claimInfo = ClaimInfo::create($ClaimInfoParams->only([
                'ClaimNo',
                'AccidentTime',
                'ReportTime',
                'ReportDelayCause',
                'AccidentCause',
                'AccidentCauseDesc',
                'IsCatastrophe',
                'CatastropheCode',
                'PropertyLossAmt',
                'InjuryLossAmt',
                'ReportType',
                'ReportName',
                'ReportTel',
                'InsuredRelation',
                'AccidentProvince',
                'AccidentCity',
                'AccidentDistrict',
                'AccidentDetailAddress',
                'AccidentDesc',
            ])->toArray());

            $SubClaimInfoParams = $ClaimInfoParams->get('SubClaimInfo');

            if ($SubClaimInfoParams) {
                $subClaimInfo = $claimInfo->subClaimInfo()->create(Arr::only($SubClaimInfoParams, [
                    'SubClaim',
                    'RiskName',
                    'SubClaimType',
                    'DamageObject',
                    'DamageDesc',
                    'Owner',
                    'TotalLoss',
                    'CertiType',
                    'CertiNo',
                    'Sex',
                    'DateOfBirth',
                    'Mobile',
                    'InjuryName',
                    'InjuryType',
                    'InjuryLevel',
                    'DisabilityGrade',
                    'Treatment',
                    'HospitalName',
                    'DateOfAdmission',
                    'DateOfDischarge',
                    'DaysInHospital',
                    'CareName',
                    'CareDays',
                    'ContactProvince',
                    'ContactCity',
                    'ContactDistrict',
                    'ContactDetailAddress',
                ]));

                $subClaimInfo->taskInfo()->create($SubClaimInfoParams['TaskInfo']);
            }
        }

        return Response::success('W01');
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
    public function investigationTask(Request $request): JsonResponse
    {
        $claims = ClaimInfo::when($request->input('status'), fn($query, $status) => $query->where('status', $status))
            ->orderBy('id', 'desc')
            ->paginate(getPerPage());

        return success($claims);
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
        Log::create([
            'type' => '定损理算推送',
            'url' => request()->fullUrl(),
            'request' => json_encode(request()->all()),
        ]);
        return Response::success('W03');
    }

    /**
     * 定损理算
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function appraisalTask(Request $request): JsonResponse
    {
        $tasks = AppraisalTask::when($request->input('status'), fn($query, $status) => $query->where('status', $status))
            ->orderBy('id', 'desc')
            ->paginate(getPerPage());
        return success($tasks);
    }


    /**
     * 接收任务关闭通知
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function receiveTaskCanceled(Request $request): JsonResponse
    {
        Log::create([
            'type' => '任务关闭推送',
            'url' => request()->fullUrl(),
            'request' => json_encode(request()->all()),
        ]);

        $request->only([
            'ClaimNo',  // 理赔编号
            'TaskID',  // 任务ID 核心任务唯一ID
            'SubClaim',  // 子赔案
            'TaskType',  // 任务类型 《公用代码》-任务类型
        ]);

        return Response::success('W05');
    }

    /**
     * 接收核赔通过通知
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function receiveAppraisalPass(Request $request): JsonResponse
    {
        Log::create([
            'type' => '核赔通过推送',
            'url' => request()->fullUrl(),
            'request' => json_encode(request()->all()),
        ]);

        $request->only([
            'ClaimNo',  // 理赔编号
            'SubClaim',  // 子赔案
            'CalculationTimes',  // 理算次数
            'IsDeclined',  // 是否拒赔 《公用代码》-是否代码
        ]);

        return Response::success('W06');
    }

    /**
     * 接收单证审核不通过通知
     *
     * @return JsonResponse
     */
    public function receiveDocRefused(): JsonResponse
    {
        Log::create([
            'type' => '单证审核不通过推送',
            'url' => request()->fullUrl(),
            'request' => json_encode(request()->all()),
        ]);
        return Response::success();
    }
}
