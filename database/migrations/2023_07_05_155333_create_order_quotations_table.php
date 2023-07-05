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
        Schema::create('order_quotations', function (Blueprint $table) {
            $table->id();
            $table->integer('order_id');
            $table->integer('company_id');
            $table->integer('plan_type')->comment('报价维修类型');
            $table->integer('repair_days')->comment('维修工期 天');
            $table->decimal('repair_cost', 12)->default(0);
            $table->decimal('other_cost', 12)->default(0);
            $table->decimal('total_cost', 12)->default(0);
            $table->decimal('profit_margin', 12)->default(0);
            $table->decimal('profit_margin_ratio')->default(0);
            $table->string('repair_remark');
            $table->decimal('total_price')->default(0)->comment('报价金额');
            $table->text('images');
            $table->tinyInteger('win')->default(0);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('order_quotations');
    }
};
