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
        Schema::create('order_repair_plans', function (Blueprint $table) {
            $table->id();
            $table->integer('company_id');
            $table->integer('order_id');
            $table->integer('plan_type');
            $table->integer('repair_type')->comment('1 自修 2 外协修');
            $table->integer('repair_days');
            $table->integer('repair_company_id')->comment('外协修');
            $table->integer('repair_user_id')->comment('自修时');
            $table->boolean('has_cost')->comment('是否有自修成本');
            $table->boolean('has_provider')->comment('是否有外协单位');
            $table->decimal('repair_cost', 12)->comment('施工成本');
            $table->text('cost_tables')->nullable();
            $table->string('plan_text')->nullable()->comment('施工方案');
            $table->integer('create_user_id');
            $table->integer('check_status')->default(0);
            $table->timestamp('checked_at')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('order_repair_plans');
    }
};
