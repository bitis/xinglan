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
        Schema::create('repair_tasks', function (Blueprint $table) {
            $table->id();
            $table->integer('company_id');
            $table->integer('repair_plan_id');
            $table->string('goods_type')->comment('受损类别');
            $table->string('goods_name')->comment('受损物品名称');
            $table->string('remark')->nullable();
            $table->integer('repair_company_id')->comment('施工单位');
            $table->decimal('repair_cost', 12)->comment('施工成本');
            $table->string('image')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('repair_tasks');
    }
};
