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
        Schema::create('messages', function (Blueprint $table) {
            $table->id();
            $table->integer('send_company_id');
            $table->integer('to_company_id');
            $table->integer('type');
            $table->string('order_id');
            $table->string('order_number');
            $table->string('case_number')->nullable();
            $table->string('goods_types');
            $table->string('remark')->nullable();
            $table->integer('accept_user_id')->nullable();
            $table->timestamp('accept_at')->nullable();
            $table->boolean('status')->default(false);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('messages');
    }
};
