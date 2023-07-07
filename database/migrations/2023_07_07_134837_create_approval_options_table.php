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
        Schema::create('approval_options', function (Blueprint $table) {
            $table->id();
            $table->integer('company_id');
            $table->integer('type');
            $table->integer('approve_type');
            $table->integer('review_type');
            $table->string('review_conditions')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('approval_options');
    }
};
