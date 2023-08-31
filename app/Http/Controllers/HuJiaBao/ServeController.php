<?php

namespace App\Http\Controllers\HuJiaBao;

use App\Common\HuJiaBao\ApiClient;
use App\Common\HuJiaBao\Response;
use App\Http\Controllers\Controller;
use App\Models\CalculationInfo;
use App\Models\HuJiaBao\AppraisalInfo;
use App\Models\HuJiaBao\AppraisalTask;
use App\Models\HuJiaBao\ClaimInfo;
use App\Models\HuJiaBao\Log;
use App\Models\HuJiaBao\PolicyInfo;
use App\Models\HuJiaBao\SubClaimInfo;
use App\Models\PayeeInfo;
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

        try {

            DB::beginTransaction();

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

                if (isset($item['BenefitList'])) $coverageInfo->benefitList()->createMany($item['BenefitList']);
            }

            $ClaimInfoParams = collect($request->collect('ClaimInfo'));

            if ($ClaimInfoParams) {
                $ClaimInfoParams = $ClaimInfoParams->put('policy_info_id', $policyInfo->id);
                $claimInfo = ClaimInfo::create($ClaimInfoParams->only([
                    'policy_info_id',
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

                    if (isset($SubClaimInfoParams['TaskInfo'])) {
                        $task = $subClaimInfo->taskInfo()->create($SubClaimInfoParams['TaskInfo']);
                        $subClaimInfo->TaskID = $task->TaskID;
                    }

                    if (isset($SubClaimInfoParams['InvestigationInfo'])) {
                        $investigationInfo = $subClaimInfo->investigationInfo()
                            ->create($SubClaimInfoParams['InvestigationInfo']);

                        if (isset($SubClaimInfoParams['InvestigationInfo']['LossItemList']))
                            $investigationInfo->lossItemList()
                                ->createMany($SubClaimInfoParams['InvestigationInfo']['LossItemList']);
                    }

                    $subClaimInfo->ClaimNo = $claimInfo->ClaimNo;
                    $subClaimInfo->save();
                }
            }

            DB::commit();
        } catch (Exception $exception) {
            DB::rollBack();
            return Response::failed('W01');
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
            $response = $client->upload(
                $request->file('files'),
                '001',
                $request->input('businessNo'),
                $request->input('directory')
            );
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
        $claims = PolicyInfo::with([
            'property',
            'property.coverageList',
            'claimInfo',
            'claimInfo.subClaimInfo',
            'claimInfo.subClaimInfo.investigationInfo',
        ])
            ->orderBy('id', 'desc')
            ->paginate(getPerPage());

        return success($claims);
    }

    /**
     * 提交查勘资料
     *
     * @param Request $request
     * @param ApiClient $client
     * @return JsonResponse
     */
    public function investigation(Request $request, ApiClient $client): JsonResponse
    {
        $subClaimInfo = SubClaimInfo::find($request->input('id'));

        if (!$subClaimInfo) return fail('案件不存在');

        try {
            DB::beginTransaction();

            $subClaimInfo->fill($request->input('SubClaimInfo'));

            $subClaimInfo->save();

            $investigationInfo = $request->collect('SubClaimInfo.InvestigationInfo')->only([
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
            ])->toArray();

            $LossItemList = $request->input('SubClaimInfo.InvestigationInfo.LossItemList');

            $subClaimInfo->investigationInfo()->updateOrCreate(['sub_claim_info_id' => $subClaimInfo->id], $investigationInfo);

            $subClaimInfo->investigationInfo->lossItemList()->delete();
            $subClaimInfo->investigationInfo->lossItemList()->createMany($LossItemList);

            $investigationInfo['LossItemList'] = $LossItemList;

            $form = collect($subClaimInfo->toArray())->except([
                'id',
                'claim_info_id',
                'created_at',
                'updated_at',
                'claim_info',
            ])->merge([
                'InvestigationInfo' => $investigationInfo,
            ])->toArray();

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
    public function receiveAppraisalTask(Request $request): JsonResponse
    {
        Log::create([
            'type' => '定损理算推送',
            'url' => request()->fullUrl(),
            'request' => json_encode(request()->all()),
        ]);

        $task = AppraisalTask::create($request->input('TaskInfo'));

        $appraisalInfo = AppraisalInfo::create($request->input('AppraisalInfo'));

        $appraisalInfo->task_id = $task->id;
        $appraisalInfo->save();

        $appraisalInfo->lossItemList()->createMany($request->input('AppraisalInfo.LossItemList'));
        $appraisalInfo->rescueFeeList()->createMany($request->input('AppraisalInfo.RescueFeeList'));

        foreach ($request->input('CalculationInfoList') as $calculationInfo) {
            $calculationInfo['appraisal_info_id'] = $appraisalInfo->id;
            CalculationInfo::create($calculationInfo);
        }

        foreach ($request->input('PayeeInfoList') as $info) {
            $info['appraisal_info_id'] = $appraisalInfo->id;
            $payeeInfo = PayeeInfo::create($info);

            $payeeInfo->indemnity()->createMany($info['IndemnityInfoList']);
        }

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
        return Response::success('W07');
    }
}
