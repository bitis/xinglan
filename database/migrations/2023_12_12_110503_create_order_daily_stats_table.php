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
        Schema::create('order_daily_stats', function (Blueprint $table) {
            $table->id();
            $table->integer('company_id');
            $table->integer('order_count')->comment('订单数');
            $table->integer('order_repair_count')->comment('维修订单数');
            $table->integer('order_mediate_count')->comment('协调订单数');
            $table->integer('order_budget_income')->comment('订单预算收入');
            $table->integer('order_real_income')->comment('订单实际收入');
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
        Schema::dropIfExists('order_daily_stats');
    }
};
