<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        /**
         * 保单信息
         */
        Schema::create('hjb_policy_infos', function (Blueprint $table) {
            $table->id();
            $table->string('PolicyNo', 50)->comment('保单号 若为团单，则录入为子保单号');
            $table->string('ProductType', 8)->comment('产品类型 《公用代码》-产品类型');
            $table->timestamp('EffectiveDate')->nullable()->comment('保单起期');
            $table->timestamp('ExpireDate')->nullable()->comment('保单止期');
            $table->string('PolicyStatus', 2)->comment('保单状态 《公用代码》-保单状态');
            $table->decimal('StandardPremium', 12)->nullable()->comment('签单保费');
            $table->timestamps();
        });

        /**
         * 房屋标的信息 隶属于保单信息PolicyInfo
         */
        Schema::create('hjb_properties', function (Blueprint $table) {
            $table->id();
            $table->integer('policy_info_id');
            $table->string('PropertyProvince')->comment('房屋所在省');
            $table->string('PropertyCity')->comment('房屋所在市');
            $table->string('PropertyDistrict')->comment('房屋所在区');
            $table->string('PropertyDetailAddress')->comment('房屋详细地址');
            $table->timestamps();
        });

        /**
         * 险别信息 隶属于房屋标的信息Property
         */
        Schema::create('hjb_coverages', function (Blueprint $table) {
            $table->id();
            $table->integer('property_id');
            $table->boolean('IsFinalLevelCt')->comment('是否最后一级');
            $table->string('CoverageCode')->comment('险别代码');
            $table->decimal('SumInsured', 12)->comment('保额');
            $table->decimal('SumPaymentAmt', 12)->comment('历史案件预估金额 下属责任历史案件预估金额之和');
            $table->timestamps();
        });

        /**
         * 责任信息 隶属于险别信息CoverageList
         */
        Schema::create('hjb_benefits', function (Blueprint $table) {
            $table->id();
            $table->integer('coverage_id');
            $table->boolean('IsFinalLevelCt')->comment('是否最后一级');
            $table->string('BenefitCode')->comment('责任代码 《公用代码》-险别代码下的责任');
            $table->decimal('SumInsured', 12)->comment('保额');
            $table->decimal('SumPaymentAmt', 12)->comment('历史案件预估金额 下属责任历史案件预估金额之和');
            $table->timestamps();
        });

        /**
         * 案件信息
         */
        Schema::create('hjb_claim_infos', function (Blueprint $table) {
            $table->id();
            $table->integer('benefit_id');
            $table->string('ClaimNo')->comment('理赔编号');
            $table->timestamp('AccidentTime')->nullable()->comment('出险时间');
            $table->timestamp('ReportTime')->nullable()->comment('报案时间');
            $table->string('ReportDelayCause')->nullable()->comment('延迟报案原因');
            $table->string('AccidentCause', 5)->comment('出险原因');
            $table->string('AccidentCauseDesc', 5)->nullable()->comment('其他意外事故描述 出险原因为其他时必传');
            $table->boolean('IsCatastrophe')->nullable()->comment('是否巨灾案件');
            $table->string('CatastropheCode')->nullable()->comment('巨灾代码');
            $table->decimal('PropertyLossAmt', 12)->comment('物损金额');
            $table->decimal('InjuryLossAmt', 12)->comment('人伤金额');
            $table->string('ReportType', 2)->comment('报案方式');
            $table->string('ReportName')->comment('报案人');
            $table->string('ReportTel')->comment('报案人电话');
            $table->string('InsuredRelation')->comment('与出险人关系 《公用代码》-与出险人关系');
            $table->string('AccidentProvince', 20)->comment('出险地点省');
            $table->string('AccidentCity', 20)->comment('出险地点市');
            $table->string('AccidentDistrict', 20)->comment('出险地点区');
            $table->string('AccidentDetailAddress')->comment('出险详细地址');
            $table->string('AccidentDesc')->comment('案件经过');
            $table->timestamps();
        });

        /**
         * 子赔案信息 隶属于案件信息ClaimInfo
         */
        Schema::create('hjb_sub_claim_infos', function (Blueprint $table) {
            $table->id();
            $table->string('SubClaim')->comment('子赔案');
            $table->string('RiskName', 600)->comment('被保标的');
            $table->string('SubClaimType', 8)->comment('子赔案类型 《公用代码》-子赔案类型');
            $table->string('DamageObject', 50)->comment('损失标的');
            $table->text('DamageDesc')->nullable()->comment('损失描述');
            $table->string('Owner')->nullable()->comment('所有人姓名');
            $table->boolean('TotalLoss')->nullable()->comment('全损');
            $table->boolean('CertiType', 2)->nullable()->comment('证件类型 子赔案类型为人伤损失时必传，包括三者人伤和标的人伤 《公用代码》-证件类型');
            $table->string('CertiNo', 30)->nullable()->comment('证件号码 子赔案类型为人伤损失时必传，包括三者人伤和标的人伤');
            $table->string('Sex', 2)->nullable()->comment('性别 《公用代码》-性别代码');
            $table->date('DateOfBirth')->nullable()->comment('出生日期');
            $table->string('Mobile', 50)->nullable()->comment('联系电话');
            $table->string('InjuryName', 200)->nullable()->comment('伤情名称');
            $table->string('InjuryType', 10)->nullable()->comment('伤情类别 《公用代码》-伤情类别');
            $table->string('InjuryLevel', 2)->nullable()->comment('伤情程度 《公用代码》-伤情程度');
            $table->string('DisabilityGrade', 10)->nullable()->comment('伤残等级 《公用代码》-伤残等级');
            $table->string('Treatment', 10)->nullable()->comment('治疗方式 《公用代码》-治疗方式');
            $table->string('HospitalName', 50)->nullable()->comment('医院名称');
            $table->timestamp('DateOfAdmission')->nullable()->comment('入院日期');
            $table->timestamp('DateOfDischarge')->nullable()->comment('出院日期');
            $table->integer('DaysInHospital')->nullable()->comment('住院天数');
            $table->string('CareName')->nullable()->comment('护理人名称');
            $table->string('CareDays')->nullable()->comment('护理天数');
            $table->string('ContactProvince', 20)->nullable()->comment('联系地址省');
            $table->string('ContactCity', 20)->nullable()->comment('联系地址市');
            $table->string('ContactDistrict', 20)->nullable()->comment('联系地址区');
            $table->string('ContactDetailAddress')->nullable()->comment('联系详细地址');
            $table->timestamps();
        });

        /**
         * 查勘任务 隶属于子赔案信息 SubClaimInfo
         */
        Schema::create('hjb_task_infos', function (Blueprint $table) {
            $table->id();
            $table->integer('sub_claim_info_id')->comment('子赔案ID');
            $table->string('TaskType')->comment('任务类型 《公用代码》-任务类型');
            $table->string('TaskID')->comment('任务ID 核心任务唯一ID');
            $table->timestamp('DueDate')->nullable()->comment('到期日');
            $table->string('InvestigationProvince', 20)->nullable()->comment('查勘地址省');
            $table->string('InvestigationCity', 20)->nullable()->comment('查勘地址市');
            $table->string('InvestigationDistrict', 20)->nullable()->comment('查勘地址区');
            $table->string('InvestigationDetailAddress')->nullable()->comment('查勘详细地址');
            $table->text('Remark')->nullable()->comment('备注');
            $table->timestamps();
        });

        /**
         * 查勘信息
         */
        Schema::create('hjb_investigation_infos', function (Blueprint $table) {
            $table->id();
            $table->integer('task_info_id')->comment('任务ID');
            $table->string('PropertyNature', 10)->comment('房屋使用性质 《公用代码》-房屋使用性质');
            $table->boolean('IsInvolveRecovery')->comment('是否涉及追偿');
            $table->string('InvestigatorContact', 30)->comment('查勘员联系方式');
            $table->timestamp('InvestigatorArrivalDate')->nullable()->comment('查勘员到达时间');
            $table->string('InvestigationProvince', 20)->comment('查勘地址省');
            $table->string('InvestigationCity', 20)->comment('查勘地址市');
            $table->string('InvestigationDistrict', 20)->comment('查勘地址区');
            $table->string('InvestigationDetailAddress', 200)->comment('查勘详细地址');
            $table->text('InvestigationDescription')->nullable()->comment('查勘描述');
            $table->decimal('PropertyTotalEstimatedAmount', 12)->comment('预估损失总金额');
            $table->text('Remark')->nullable()->comment('备注');
            $table->timestamps();
        });

        /**
         * 损失清单
         */
        Schema::create('hjb_loss_items', function (Blueprint $table) {
            $table->id();
            $table->integer('task_info_id')->comment('任务ID');
            $table->integer('SequenceNo')->comment('序号');
            $table->string('LossItemName', 200)->nullable()->comment('损失项目名称 子赔案类型为财产损失时必传');
            $table->string('LossItemType', 6)->nullable()->comment('损失项目类型 子赔案类型为人伤损失时必传 《公用代码》-损失项目类型');
            $table->string('BenefitCode', 200)->comment('险别代码 《公用代码》-险别代码下的责任');
            $table->integer('Number')->nullable()->comment('数量 子赔案类型为财产损失时必填');
            $table->decimal('UnitPrice', 12)->nullable()->comment('单价 子赔案类型为财产损失时必填');
            $table->decimal('Salvage', 12)->nullable()->comment('残值 子赔案类型为财产损失时必填');
            $table->decimal('EstimatedAmount', 12)->comment('预估金额');
            $table->text('Remark')->nullable()->comment('备注');
            $table->timestamps();
        });

        /**
         * 定损理算任务
         */
        Schema::create('hjb_appraisal_task_infos', function (Blueprint $table) {
            $table->id();
            $table->integer('sub_claim_info_id')->comment('子赔案ID');
            $table->string('ClaimNo')->comment('理赔编号');
            $table->string('TaskID')->comment('任务ID 核心任务唯一ID');
            $table->integer('DueDate')->comment('当前理算次数');
            $table->boolean('IsConfirmed')->comment('客户已赔付确认');
            $table->text('Remark')->nullable()->comment('备注');
            $table->timestamps();
        });

        /**
         * 定损信息
         */
        Schema::create('hjb_appraisal_infos', function (Blueprint $table) {
            $table->id();
            $table->string('RiskName', 200)->comment('被保标的');
            $table->string('SubClaimType', 10)->comment('子赔案类型 《公用代码》-子赔案类型');
            $table->string('DamageObject', 200)->comment('损失标的');
            $table->boolean('IsConfirmed')->comment('客户已赔付确认');
            $table->string('Owner')->nullable()->comment('所有人姓名');
            $table->boolean('TotalLoss')->comment('是否全损');
            $table->string('CertiType', 2)->nullable()->comment('证件类型 子赔案类型为人伤损失时必传《公用代码》-证件类型');
            $table->string('CertiNo', 50)->nullable()->comment('证件号码 子赔案类型为人伤损失时必传');
            $table->string('Sex', 2)->nullable()->comment('性别 《公用代码》-性别代码');
            $table->date('DateOfBirth')->nullable()->comment('出生日期');
            $table->string('Mobile', 50)->nullable()->comment('出生日期');
            $table->string('InjuryName', 200)->nullable()->comment('伤情名称');
            $table->string('InjuryType', 10)->nullable()->comment('伤情类别 《公用代码》-伤情类别');
            $table->string('InjuryLevel', 2)->nullable()->comment('伤情程度');
            $table->string('DisabilityGrade', 10)->nullable()->comment('伤残等级 《公用代码》-伤残等级');
            $table->string('Treatment', 10)->nullable()->comment('治疗方式 《公用代码》-治疗方式');
            $table->string('HospitalName', 50)->nullable()->comment('医院名称 《公用代码》-治疗方式');
            $table->timestamp('DateOfAdmission')->nullable()->comment('入院日期');
            $table->timestamp('DateOfDischarge')->nullable()->comment('出院日期');
            $table->integer('DaysInHospital')->nullable()->comment('住院天数');
            $table->string('CareName')->nullable()->comment('护理人名称');
            $table->string('CareDays')->nullable()->comment('护理天数');
            $table->string('ContactProvince', 20)->nullable()->comment('联系地址省');
            $table->string('ContactCity', 20)->nullable()->comment('联系地址市');
            $table->string('ContactDistrict', 20)->nullable()->comment('联系地址区');
            $table->string('ContactDetailAddress')->nullable()->comment('联系详细地址');
            $table->text('DamageDescription')->nullable()->comment('损失描述');
            $table->string('AppraisalType', 10)->nullable()->comment('定损类型');
            $table->decimal('TotalLossAmount', 12)->default(0)->comment('总损失金额');
            $table->decimal('TotalRescueAmount', 12)->default(0)->comment('总损失金额');
            $table->text('Remark')->nullable()->comment('备注');
            $table->timestamps();
        });

        /**
         * 损失信息列表
         */
        Schema::create('hjb_appraisal_loss_items', function (Blueprint $table) {
            $table->id();
            $table->integer('appraisal_info_id')->comment('任务ID');
            $table->string('OperationType', 6)->comment('操作类型 《公用代码》-操作类型');
            $table->integer('SequenceNo', 6)->nullable()->comment('损失项目ID 每个损失项目的ID，若为新增项目，传空');
            $table->integer('AppraisalTimes')->nullable()->comment('定损次数 当次理算次数');
            $table->string('LossItemName', 200)->nullable()->comment('损失项目名称 子赔案类型为财产损失时必传');
            $table->string('LossItemType', 6)->nullable()->comment('损失项目类型 《公用代码》-损失项目类型');
            $table->string('BenefitCode', 200)->comment('险别代码 《公用代码》-险别代码下的责任');
            $table->integer('Number')->nullable()->comment('数量 子赔案类型为财产损失时必填');
            $table->decimal('UnitPrice', 12)->nullable()->comment('单价 子赔案类型为财产损失时必填');
            $table->decimal('Salvage', 12)->nullable()->comment('残值 子赔案类型为财产损失时必填');
            $table->decimal('LossAmount', 12)->comment('损失金额');
            $table->text('Remark')->nullable()->comment('备注');
            $table->timestamps();
        });

        /**
         * 施救费信息列表
         */
        Schema::create('hjb_rescue_fees', function (Blueprint $table) {
            $table->id();
            $table->integer('appraisal_info_id')->comment('任务ID');
            $table->string('OperationType', 6)->comment('操作类型 《公用代码》-操作类型');
            $table->integer('SequenceNo', 6)->nullable()->comment('损失项目ID 每个损失项目的ID，若为新增项目，传空');
            $table->integer('AppraisalTimes')->nullable()->comment('定损次数 当次理算次数');
            $table->string('RescueUnit')->nullable()->comment('施救单位');
            $table->string('BenefitCode', 200)->comment('险别代码 《公用代码》-险别代码下的责任');
            $table->decimal('RescueAmount', 12)->comment('施救金额 《公用代码》-险别代码下的责任');
            $table->text('Remark')->nullable()->comment('备注');
            $table->timestamps();
        });

        /**
         * 理算信息列表
         */
        Schema::create('hjb_calculation_infos', function (Blueprint $table) {
            $table->id();
            $table->integer('appraisal_info_id')->comment('任务ID');
            $table->string('OperationType', 6)->comment('操作类型 《公用代码》-操作类型');
            $table->integer('SequenceNo', 6)->nullable()->comment('损失项目ID 每个损失项目的ID，若为新增项目，传空');
            $table->integer('CalculationTimes')->nullable()->comment('理算次数 当次理算次数');
            $table->integer('ReserveType')->nullable()->comment('赔偿类型 《公用代码》-赔偿类型');
            $table->string('BenefitCode', 100)->comment('险别代码 《公用代码》-险别代码下的责任');
            $table->decimal('RequestedAmount', 12)->comment('损失金额');
            $table->decimal('Deductible', 12)->comment('免赔额');
            $table->float('AccidentLiabilityRatio')->comment('事故责任比例');
            $table->decimal('PreviousRecognizedAmount', 12)->comment('前次损失确认金额');
            $table->decimal('TotalRecognizedAmount', 12)->comment('累计损失确认金额');
            $table->decimal('PreviousAdjustedAmount', 12)->comment('前次理算确认金额');
            $table->decimal('CalculationAmount', 12)->comment('理算金额');
            $table->decimal('AdjustedAmount', 12)->comment('理算确认金额');
            $table->decimal('TotalAdjustedAmount', 12)->comment('累计理算确认金额');
            $table->string('CalculationFormula', 20)->comment('理算金额计算公式代码 《公用代码》-理算公式');
            $table->boolean('IsDeclined')->nullable()->comment('是否拒赔');
            $table->timestamps();
        });

        /**
         * 领款人信息
         */
        Schema::create('hjb_payee_info', function (Blueprint $table) {
            $table->id();
            $table->integer('appraisal_task_info')->comment('任务ID');
            $table->integer('SequenceNo')->comment('领款人ID');
            $table->integer('CalculationTimes')->comment('理算次数');
            $table->string('PayeeName')->nullable()->comment('收款人姓名');
            $table->string('PayMode', 3)->nullable()->comment('支付方式 《公用代码》-支付方式');
            $table->string('AccountType', 3)->nullable()->comment('对公/对私 《公用代码》-对公对私');
            $table->string('BankCode', 20)->nullable()->comment('银行代码 《公用代码》-银行代码');
            $table->string('BankName', 100)->nullable()->comment('银行名称');
            $table->string('OpenAccountBranchName', 100)->nullable()->comment('开户支行名称');
            $table->string('AccountName', 100)->nullable()->comment('账户名称');
            $table->string('BankCardNo', 100)->nullable()->comment('银行卡号');
            $table->decimal('TotalIndemnityAmount', 12)->nullable()->comment('赔偿金额总计');
        });

        /**
         * 赔偿信息 隶属于领款人信息
         */
        Schema::create('hjb_indemnity_infos', function (Blueprint $table) {
            $table->id();
            $table->integer('payee_info_id');
            $table->integer('SequenceNo')->comment('赔偿信息ID');
            $table->string('ReserveType', 2)->nullable()->comment('赔偿类型 《公用代码》-赔偿类型');
            $table->string('BenefitCode', 100)->nullable()->comment('险别代码 《公用代码》-险别代码下的责任');
            $table->decimal('UnrecognizedAmount', 12)->nullable()->comment('未决预估金额');
            $table->decimal('IndemnityAmount', 12)->nullable()->comment('赔偿金额');
            $table->text('IndemnityAmount')->nullable()->comment('备注');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('hjb_policy_infos');
        Schema::dropIfExists('hjb_properties');
        Schema::dropIfExists('hjb_coverages');
        Schema::dropIfExists('hjb_benefits');
        Schema::dropIfExists('hjb_claim_infos');
        Schema::dropIfExists('hjb_sub_claim_infos');
        Schema::dropIfExists('hjb_task_infos');
        Schema::dropIfExists('hjb_investigation_infos');
        Schema::dropIfExists('hjb_loss_items');
    }
};
