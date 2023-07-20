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
        Schema::create('order_insurers', function (Blueprint $table) {
            $table->id();
            $table->integer('order_id');
            $table->integer('insurer_id');
            $table->integer('type');
            $table->integer('policy_number');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('order_insurers');
    }
};
