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
        Schema::create('orders', function (Blueprint $table) {
            $table->id();
            $table->string('case_number');
            $table->string('external_number')->comment('外部案件号');
            $table->string('company_id');
            $table->string('insurance_type');
            $table->string('license_plate')->comment('车牌');
            $table->string('vin')->comment('车架号');
            $table->string('locations')->comment('经纬度坐标，经度在前，纬度在后');
            $table->string('province');
            $table->string('city');
            $table->string('area');
            $table->string('address');
            $table->string('creator_id');
            $table->string('creator_name');
            $table->string('insurance_people');
            $table->string('insurance_phone');
            $table->string('driver_name');
            $table->string('driver_phone');
            $table->string('remark');
            $table->string('customer_remark');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('orders');
    }
};
