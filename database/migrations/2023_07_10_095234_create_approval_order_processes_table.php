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
        Schema::create('approval_order_processes', function (Blueprint $table) {
            $table->id();
            $table->integer('approval_order_id');
            $table->integer('company_id');
            $table->integer('user_id');
            $table->integer('step');
            $table->tinyInteger('approval_status')->default(0);
            $table->string('remark')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->boolean('hidden')->default(false);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('approval_order_processes');
    }
};
