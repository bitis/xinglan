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
        Schema::create('quotation_items', function (Blueprint $table) {
            $table->id();
            $table->integer('order_quotation_id')->comment('报价表ID');
            $table->string('sort_num');
            $table->string('name');
            $table->string('specs');
            $table->string('unit');
            $table->integer('number');
            $table->decimal('price', 12);
            $table->decimal('total_price', 12);
            $table->string('remark');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('quotation_items');
    }
};
