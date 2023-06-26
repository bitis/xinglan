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
        Schema::create('provider_options', function (Blueprint $table) {
            $table->id();
            $table->integer('provider_id');
            $table->tinyInteger('insurance_type');
            $table->string('province');
            $table->string('city');
            $table->string('area');
            $table->float('weight')->default(0);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('provider_options');
    }
};
