<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateCompaniesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('companies', function (Blueprint $table) {
            $table->id();
            $table->integer('parent_id');
            $table->integer('invite_code');
            $table->integer('type');
            $table->integer('level');
            $table->string('name');
            $table->string('contract_name');
            $table->string('contract_phone');
            $table->string('province');
            $table->string('city');
            $table->string('area');
            $table->string('address');
            $table->boolean('status');
            $table->string('bank_name');
            $table->string('bank_account_name');
            $table->string('bank_account_number');
            $table->string('official_seal');
            $table->string('logo');
            $table->string('remark');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('companies');
    }
}
