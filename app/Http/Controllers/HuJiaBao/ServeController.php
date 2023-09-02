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
     * @param ApiClient $client
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
            'property.coverageList.benefitList',
            'claimInfo',
            'claimInfo.subClaimInfo',
            'claimInfo.subClaimInfo.investigationInfo',
            'claimInfo.subClaimInfo.investigationInfo.lossItemList',
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

            $investigationInfo['InvestigatorArrivalDate'] = date('Y-m-d\TH:i:s', strtotime($investigationInfo['InvestigatorArrivalDate']));

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
                'DamageDesc',
                'investigation_info',
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
     * @param Request $request
     * @return JsonResponse
     */
    public function receiveAppraisalTask(Request $request): JsonResponse
    {
        $log = Log::create([
            'type' => '定损理算推送',
            'url' => request()->fullUrl(),
            'request' => json_encode(request()->all()),
        ]);

        try {

            DB::beginTransaction();
            $task = AppraisalTask::create($request->input('TaskInfo'));

            $appraisalInfo = AppraisalInfo::create($request->input('AppraisalInfo'));

            $appraisalInfo->task_id = $task->id;
            $appraisalInfo->save();

            if ($request->input('AppraisalInfo.LossItemList'))
                $appraisalInfo->lossItemList()->createMany($request->input('AppraisalInfo.LossItemList'));
            if ($request->input('AppraisalInfo.RescueFeeList'))
                $appraisalInfo->rescueFeeList()->createMany($request->input('AppraisalInfo.RescueFeeList'));

            foreach ($request->input('CalculationInfoList', []) as $calculationInfo) {
                $calculationInfo['appraisal_info_id'] = $appraisalInfo->id;
                CalculationInfo::create($calculationInfo);
            }

            foreach ($request->input('PayeeInfoList', []) as $info) {
                $info['appraisal_info_id'] = $appraisalInfo->id;
                $payeeInfo = PayeeInfo::create($info);

                $payeeInfo->indemnity()->createMany($info['IndemnityInfoList']);
            }

            DB::commit();
        } catch (Exception $exception) {
            DB::rollBack();
            $log->response = $exception->getMessage();
            $log->save();
//            return Response::failed('W03', $exception->getMessage());
        }

        return Response::success('W03');
    }

    /**
     * 定损理算任务列表
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function appraisalTask(Request $request): JsonResponse
    {
        $tasks = AppraisalTask::with([
            'info',
            'info.lossItemList',
            'info.rescueFeeList',
            'calculationInfoList',
            'payeeInfoList',
            'payeeInfoList.indemnity',
        ])
            ->when($request->input('status'), fn($query, $status) => $query->where('status', $status))
            ->orderBy('id', 'desc')
            ->paginate(getPerPage());
        return success($tasks);
    }

    /**
     * 定损理算回传
     *
     * @param Request $request
     * @param ApiClient $client
     * @return JsonResponse
     */
    public function appraisal(Request $request, ApiClient $client): JsonResponse
    {
        $task = AppraisalTask::find($request->input('id'));

        if (empty($task)) return fail('任务不存在');

        try {
            DB::beginTransaction();

            $info = $task->info;

            $task->fill($request->input('TaskInfo'));
            $task->save();

            $info->fill($request->collect('AppraisalInfo')->only([
                "RiskName",
                "SubClaimType",
                "DamageObject",
                "Owner",
                "TotalLoss",
                "CertiType",
                "CertiNo",
                "Sex",
                "DateOfBirth",
                "Mobile",
                "InjuryName",
                "InjuryType",
                "InjuryLevel",
                "DisabilityGrade",
                "Treatment",
                "HospitalName",
                "DateOfAdmission",
                "DateOfDischarge",
                "DaysInHospital",
                "CareName",
                "CareDays",
                "ContactProvince",
                "ContactCity",
                "ContactDistrict",
                "ContactDetailAddress",
                "DamageDescription",
                "AppraisalType",
                "TotalLossAmount",
                "TotalRescueAmount",
            ])->toArray());

            $info->save();

            $info->lossItemList()->delete();
            $lossItemList = $info->lossItemList()->createMany($request->collect('AppraisalInfo.LossItemList'));

            $info->rescueFeeList()->delete();
            $rescueFeeList = $info->rescueFeeList()->createMany($request->collect('AppraisalInfo.RescueFeeList'));

            $task->calculationInfoList()->delete();
            $calculationInfoList = $task->calculationInfoList()->createMany($request->collect('CalculationInfoList'));

            $task->payeeInfoList()->delete();
            $payeeInfoList = $task->payeeInfoList()->createMany($request->collect('PayeeInfoList'));

            DB::commit();
        } catch (Exception $exception) {
            DB::rollBack();
            return fail('数据异常');
        }

        try {
            $form = [
                'TaskInfo' => collect($task->attributesToArray())->except([
                    'id', 'status', 'created_at', 'updated_at'
                ])->whereNotNull()->toArray(),
                'AppraisalInfo' => collect($info->attributesToArray())->except([
                    'id', 'task_id', 'created_at', 'updated_at'
                ])->whereNotNull()->merge([
                    'LossItemList' => $lossItemList->toArray(),
                    'RescueFeeList' => $rescueFeeList->toArray()
                ])->toArray(),
                'CalculationInfoList' => $calculationInfoList->toArray(),
                'PayeeInfoList' => $payeeInfoList->toArray()
            ];

            $client->appraisal($form);

            $task->status = 1;
            $task->save();

        } catch (Exception $exception) {
            return fail($exception->getMessage());
        }
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
        Log::create([
            'type' => '任务关闭推送',
            'url' => request()->fullUrl(),
            'request' => json_encode(request()->all()),
        ]);

        $where = $request->only([
            'ClaimNo',  // 理赔编号
            'TaskID',  // 任务ID 核心任务唯一ID
            'SubClaim',  // 子赔案
        ]);

        if ($request->input('TaskType') == '01') {
            $subClaims = SubClaimInfo::where($where)->get();
            SubClaimInfo::where($where)->update(['status' => 2]);

            foreach ($subClaims as $subClaim) {
                $policyInfo = $subClaim->claimInfo->policyInfo;
                $policyInfo->status = 2;
                $policyInfo->save();
            }
        } else {
            $appraisalTask = AppraisalTask::where($where)->first();
            $appraisalTask->status = 2;
            $appraisalTask->save();
        }

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

        $task = AppraisalTask::where($request->only(['ClaimNo', 'SubClaim']))->first();

        $task->CalculationTimes = $request->input('CalculationTimes');
        $task->IsDeclined = $request->input('IsDeclined');
        $task->AppraisalPassAt = now()->toDateTimeString();
        $task->save();

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
