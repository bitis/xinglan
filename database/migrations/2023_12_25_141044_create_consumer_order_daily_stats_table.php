<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('consumer_order_daily_stats', function (Blueprint $table) {
            $table->id();
            $table->integer('company_id')->comment('物损公司ID');
            $table->integer('insurance_company_id')->comment('保险公司ID');
            $table->integer('order_count')->default(0)->comment('订单数');
            $table->integer('order_repair_count')->default(0)->comment('维修订单数');
            $table->integer('order_mediate_count')->default(0)->comment('协调订单数');
            $table->date('date');
            $table->softDeletes();
            $table->index(['company_id', 'date']);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('consumer_order_daily_stats');
    }
};
