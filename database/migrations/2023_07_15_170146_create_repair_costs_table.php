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
        Schema::create('repair_costs', function (Blueprint $table) {
            $table->id();
            $table->integer('company_id');
            $table->integer('repair_plan_id');
            $table->string('name');
            $table->decimal('money', 12);
            $table->string('remark');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('repair_costs');
    }
};
