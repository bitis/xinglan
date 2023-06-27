<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('goods_prices', function (Blueprint $table) {
            $table->id();
            $table->integer('company_id')->default(0)->nullable();
            $table->string('company_name')->nullable();
            $table->string('province')->nullable();
            $table->string('city')->nullable();
            $table->string('region')->nullable();
            $table->integer('cat_id')->nullable();
            $table->string('cat_name')->nullable();
            $table->string('cat_parent_id');
            $table->string('product_name')->nullable();
            $table->string('spec')->nullable();
            $table->string('unit')->nullable();
            $table->string('brand')->nullable();
            $table->decimal('unit_price', 12, 2)->nullable();
            $table->string('describe_image')->nullable();
            $table->text('remark')->nullable();
            $table->boolean('status')->default(1);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('goods_prices');
    }
};
