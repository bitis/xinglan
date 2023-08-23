<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('financial_orders', function (Blueprint $table) {
            $table->id();
            $table->integer('company_id')->comment('公司ID');
            $table->integer('order_id')->comment('工单ID');
            $table->integer('opposite_company_id')->comment('对方（公司）ID');
            $table->integer('opposite_company_name')->comment('对方（公司）名称');
            $table->decimal('total_amount', 12)->comment('应收、付总金额');
            $table->decimal('paid_amount', 12)->comment('已收、付款金额');
            $table->decimal('invoiced_amount', 12)->comment('已开票金额');
            $table->tinyInteger('payment_status')->comment('收、付款状态');
            $table->tinyInteger('invoice_status')->comment('开票状态');
            $table->timestamp('paid_at')->nullable()->comment('付款时间');
            $table->timestamps();
        });

        Schema::create('financial_invoice_records', function (Blueprint $table) {
            $table->id();
            $table->integer('financial_order_id')->comment('财务工单ID');
            $table->integer('company_id')->comment('公司ID');
            $table->integer('order_id')->comment('工单ID');
            $table->string('order_number')->comment('工单号');
            $table->integer('customer_id')->comment('客户（公司）ID');
            $table->integer('customer_name')->comment('客户（公司）名称');
            $table->decimal('paid_amount', 12)->comment('付款金额');
            $table->tinyInteger('invoice_type')->comment('发票类型');
            $table->string('invoice_number')->comment('	发票号');
            $table->decimal('invoice_amount', 12)->comment('发票金额');
            $table->integer('bank_account_id')->comment('收、付款银行账号');
            $table->string('proof', 12)->comment('收、付款凭证');
            $table->string('case_number')->comment('报案号');
            $table->string('license_plate', 10)->comment('车牌号');
            $table->tinyInteger('payment_status')->comment('收、付款状态');
            $table->tinyInteger('invoice_status')->comment('开票状态');
            $table->integer('invoice_operator_id')->comment('开票人ID');
            $table->string('invoice_operator_name', 20)->comment('开票人名称');
            $table->integer('payment_operator_id')->comment('开票人ID');
            $table->string('payment_operator_name', 20)->comment('开票人名称');
            $table->string('express_company_name', 20)->comment('快递公司名字');
            $table->string('express_order_number', 20)->comment('快递单号');
            $table->string('remark')->comment('备注');
            $table->timestamps();
        });

        Schema::create('financial_payment_records', function (Blueprint $table) {
            $table->id();
            $table->integer('company_id')->comment('所属公司ID');
            $table->string('company_name', 20)->comment('所属公司名称');
            $table->integer('customer_id')->comment('客户公司ID');
            $table->string('customer_name', 20)->comment('客户公司名称');
            $table->integer('provider_id')->comment('外协公司ID');
            $table->string('provider_name', 20)->comment('外协公司名称');
            $table->string('order_id')->comment('工单ID');
            $table->string('order_number')->comment('工单号');
            $table->integer('bank_account_id')->comment('银行账号ID');
            $table->integer('bank_name')->comment('银行名称');
            $table->string('bank_account_number', 20)->comment('银行账号');
            $table->decimal('amount', 12)->comment('金额');
            $table->tinyInteger('invoice_type')->comment('发票类型');
            $table->string('invoice_number')->comment('	发票号');
            $table->decimal('invoice_amount', 12)->comment('发票金额');
            $table->integer('invoice_company_id')->comment('开票单位ID');
            $table->integer('invoice_company_name')->comment('开票单位');
            $table->integer('operator_id')->comment('操作人ID');
            $table->string('operator_name', 20)->comment('操作人名称');
            $table->string('remark');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('financial_pay_records');
        Schema::dropIfExists('financial_payment_records');
    }
};
