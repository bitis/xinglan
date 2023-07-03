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
        Schema::create('bid_options', function (Blueprint $table) {
            $table->id();
            $table->integer('company_id');
            $table->integer('min_goods_price');
            $table->integer('mid_goods_price');
            $table->integer('working_time_deadline_min');
            $table->integer('resting_time_deadline_min');
            $table->integer('working_time_deadline_mid');
            $table->integer('resting_time_deadline_mid');
            $table->integer('working_time_deadline_max');
            $table->integer('resting_time_deadline_max');
            $table->boolean('status');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('bid_options');
    }
};
