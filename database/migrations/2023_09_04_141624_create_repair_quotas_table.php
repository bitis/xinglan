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
        Schema::create('repair_quotas', function (Blueprint $table) {
            $table->id();
            $table->integer('order_id');
            $table->integer('repair_company_id');
            $table->string('repair_company_name');
            $table->decimal('total_price');
            $table->timestamp('submit_at')->nullable();
            $table->integer('operator_id');
            $table->integer('operator_name');
            $table->tinyInteger('win');
            $table->timestamp('quota_finished_at');
            $table->string('remark');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('repair_quotas');
    }
};
